# Module Marketplace multi-vendeurs

Place de marché intégrée à l'application MALKUTHAR : des praticiens / vendeurs
créent un compte dédié, publient des offres (produits physiques, produits
digitaux, services, formations) **validées par un administrateur** avant mise en
ligne, et sont réglés via un module de **commission + versements distinct du
programme d'affiliation**.

Tout le module est masqué tant que le drapeau `marketplace.enabled` est faux.

---

## 1. Activation

| Paramètre (`Setting`, sinon `config/marketplace.php`) | Rôle | Défaut |
|---|---|---|
| `marketplace.enabled` | Drapeau maître. Faux ⇒ routes publiques + espace vendeur en 404, entrées de nav masquées. | `false` |
| `marketplace.default_commission_rate` | Taux plateforme si aucune règle ne s'applique. | `0.15` |
| `marketplace.settlement_hold_days` | Jours entre « payé » et « disponible » (fenêtre de rétractation). | `14` |
| `marketplace.min_payout_amount` | Seuil d'une demande de versement. | `5000` |
| `marketplace.payout_schedule` | `on_demand` \| `weekly` \| `biweekly`. | `on_demand` |
| `marketplace.payout_account_cooldown_hours` | Délai de sécurité après changement d'un moyen de versement. | `24` |
| `marketplace.vendor_self_publish` | Si faux, toute mise en ligne repasse en revue. | `false` |
| `marketplace.auto_approve_trusted_vendors` | Court-circuite la revue pour les vendeurs `is_trusted`. | `false` |
| `marketplace.growth_commission_rate` | Taux du palier « Growth » (page Tarifs) — voir §6bis. | `0.10` |
| `marketplace.growth_threshold_amount` | Seuil de CA cumulé à partir duquel le palier Growth est proposé. | `3 000 000` |

Activer/désactiver et régler les paliers depuis l'admin : **`/admin/marketplace/settings`**
(`Admin\Marketplace\SettingsController`, lien sidebar « Réglages MP ») — préférer
cet écran à une écriture directe en base, pour garder l'audit (§68).
Équivalent programmatique :

```php
App\Models\Setting::put('marketplace.enabled', '1');
```

### ⚠️ Avant un lancement public réel

Le module est fonctionnellement complet, mais le **paiement du panier
marketplace n'est aujourd'hui que `cod`** (encaissement manuel côté admin,
`/admin/marketplace/orders`) — il n'y a **pas encore** de redirection vers
FedaPay/CinetPay comme pour la boutique maison (`PaymentGateway` §7 les gère
déjà pour les commandes `Order`, mais le contrat n'a pas été élargi aux
`MarketplaceOrder`). Ouvrir la place de marché à de vrais clients suppose donc
soit d'accepter le COD comme seul mode de paiement, soit d'élargir
`PaymentGateway`/`ProcessPaymentWebhook` aux commandes marketplace avant.

Planification : `marketplace:release-earnings` tourne toutes les heures
(`bootstrap/app.php`) — libère les revenus vendeurs échus.

---

## 2. Rôles & accès

- **Vendeur** : compte `users` + `vendor_profiles` avec `status = active`.
  Middleware `vendor` (alias). Espace `/vendeur`.
- **Admin** : `users.role = 'admin'`. Middleware `admin`. Écrans
  `/admin/marketplace/*`.
- **Acheteur** : n'importe quel visiteur ; compte requis seulement pour laisser
  un avis.

---

## 3. Onboarding vendeur (SOP admin)

1. Le candidat remplit `/vendeur/devenir` (nom de boutique, activité, région) et
   accepte les [conditions vendeur](/conditions-vendeurs).
2. `vendor_profiles` est créé en `pending`.
3. Admin : `/admin/marketplace/vendors?status=pending` → **Approuver** /
   **Refuser (motif)**.
4. À l'approbation : `status = active`, notification `VendorApplicationApproved`,
   l'espace `/vendeur` s'ouvre.
5. Suspension : `/admin/marketplace/vendors/{id}` → **Suspendre (motif)**. Effets :
   offres masquées (scope `publiclyVisible`), revenus **non** libérés par
   `release-earnings`, versements bloqués. **Aucune donnée financière supprimée.**
   Réactivation remet `status = active`.
6. Taux de commission propre au vendeur : champ **Commission (%)** sur la fiche.

---

## 4. Modération des offres (SOP admin)

Cycle : `draft → pending_review → (approved | changes_requested | rejected)`.

- File : `/admin/marketplace/offers?status=pending_review`.
- Écran de revue : contenu **soumis** (dernière révision `pending`), diff implicite
  vs version en ligne, historique des décisions.
- **Approuver** : le `payload` de la révision est recopié sur la ligne cœur
  (la boutique lit toujours la ligne cœur). 1re approbation ⇒ `is_online = true`
  + `first_published_at`. Ré-approbation ⇒ visibilité inchangée.
- **Demander des corrections (note)** : `changes_requested`, la version en ligne
  reste.
- **Refuser (motif)** : offre jamais publiée ⇒ `rejected` ; offre déjà en ligne
  ⇒ reste `approved` + `review_note` (la version publique ne bouge pas).
- **Suspendre / Rétablir** une offre en ligne.

**Édition d'une offre en ligne par le vendeur** : les modifications sont mises en
attente dans une révision `pending` — la ligne cœur (donc la boutique) n'est PAS
touchée. Le vendeur re-soumet ; à l'approbation, le contenu remplace la version
publique.

Vendeurs `is_trusted` + `auto_approve_trusted_vendors = true` ⇒ soumission
auto-approuvée.

---

## 5. Catalogue dynamique

`/admin/marketplace/categories` :

- Arbre à profondeur libre (`path` matérialisé). Anti-cycle au reparentage ;
  suppression bloquée si sous-catégories ou offres rattachées.
- Par catégorie : types d'offres admis, barème de commission optionnel, règles
  (`require_approval`, `min_price`, `max_price`, `max_images`,
  `requires_shipping`, `allow_variants`).
- **Champs personnalisés** typés (`text`, `number`, `select`, …), `is_required`,
  `is_filterable` (facette storefront), `is_variant_axis`. Une offre hérite des
  champs de sa catégorie **et de tous ses ancêtres** ; en cas de collision de
  clé, la définition la plus proche de la feuille gagne
  (`CategoryFieldSchemaResolver`).

---

## 6. Résolution de la commission

`MarketplaceCommissionResolver`, ordre de priorité :

1. `vendor_profiles.commission_rate_override` ;
2. règle `marketplace_commission_rules` active couvrant la date, la plus
   spécifique (`vendor` > `category` > `offer_type` > `platform`) puis la plus
   prioritaire ;
3. `marketplace_categories.commission_rate` (en remontant l'arbre) ;
4. `marketplace.default_commission_rate`.

Le taux **et** la règle sont figés sur `marketplace_suborders` à l'achat.

### 6bis. Page publique « Tarifs vendeur »

`/marketplace` (`MarketplaceLandingController`) affiche, une fois le module
ouvert, trois paliers — Starter (taux par défaut), Growth (taux réduit, seuil
de CA), Scale (négocié). **Seul Starter est appliqué automatiquement** par le
résolveur ci-dessus. Growth/Scale passent toujours par une action manuelle
d'un administrateur (`/admin/marketplace/vendors/{vendor}` → override de taux),
cohérent avec le reste du module (validation admin systématique) : un vendeur
qui atteint le seuil ne bascule pas seul, il est recontacté puis basculé.

---

## 7. Panier, commande, paiement

- Panier en session (`/place-de-marche/panier`).
- Checkout (`/place-de-marche/commander`) : `MarketplaceCheckoutService` scinde le
  panier **par vendeur** en `marketplace_suborders`, fige la commission, réserve
  le stock physique, crée `marketplace_orders` (référence `MP-…`).
- **Paiement** : mode `cod` (à la livraison / manuel) par défaut. Le paiement PSP
  redirigé nécessite l'élargissement du contrat `PaymentGateway` (typé
  `App\Models\Order`) — d'ici là, l'admin encaisse via
  `/admin/marketplace/orders` → **Encaisser**. Le webhook générique
  `POST /webhooks/{provider}` aiguille déjà les références `MP-` vers
  `MarketplacePaymentConfirmationService` (branche dans `ProcessPaymentWebhook`).
- Confirmation de paiement (idempotente) : commande + sous-commandes `paid`,
  stock réservé → vendu, `sales_count`/`seats_taken` incrémentés, crédit du
  registre vendeur (`VendorSettlementService::recordSale`, `pending` jusqu'à
  `paid_at + hold_days`), notifications acheteur + vendeurs.
  **Aucune commission d'affiliation n'est créée.**
- Annulation d'une commande non payée : libère le stock réservé.

---

## 8. Règlement & versements

Registre immuable `vendor_ledger_entries` (+ journal des transitions) ;
cache `vendor_balances` recalculable à tout moment.

- `marketplace:release-earnings` (horaire) : `pending → available` pour les
  crédits échus, hors vendeurs suspendus.
- **Remboursement d'une sous-commande** (`/admin/marketplace/orders/{id}` →
  *Rembourser cette part*) : `MarketplaceRefundService` reprend le règlement
  (`refund_reversal`, report négatif possible), remet stock/places, passe la
  commande en `refunded` si toutes les parts sont reprises.
- **Ajustement manuel** : `VendorSettlementService::adjust()` (crédit si montant
  ≥ 0), tracé `admin_id` + `adjustment_reason`.

Versements (`/admin/marketplace/payouts`) :

1. Vendeur : `/vendeur/versements` → ajoute un moyen de versement, demande un
   retrait (≥ seuil, ≤ solde disponible ; cooldown après changement de moyen).
   `available → reserved`, `vendor_payouts` en `requested`, écriture ledger
   `debit / payout / processing`.
2. Admin : **Approuver** → **Marquer payé** (réf. prestataire) ⇒ écriture
   `processing → paid`, `lifetime_paid` ↑, réservé libéré ; ou **Marquer
   échoué (motif)** ⇒ écriture `processing → cancelled`, fonds restitués au
   disponible.

L'exécution automatisée via un `PayoutGateway` réel (Mobile Money / banque) est
un branchement ultérieur — l'abstraction existe déjà côté affiliation.

---

## 9. Traitement & avis

- Vendeur (`/vendeur/commandes/{suborder}`) : **Marquer expédié**
  (transporteur / suivi), avancer `delivered` / `completed` (commande `completed`
  quand toutes les parts le sont), **Confirmer** un rendez-vous (service).
- Avis acheteur : compte requis + preuve d'achat (ligne de commande payée), un
  avis par ligne. `POST /place-de-marche/avis`. Réponse du vendeur depuis sa
  fiche commande. Modération admin (`published` / `hidden`) →
  `MarketplaceOffer::recomputeRating()`.

---

## 10. Namespaces & tests

- Modèles `App\Models\Marketplace\*`, services `App\Services\Marketplace\*`,
  contrôleurs `App\Http\Controllers\{Vendor,Marketplace,Admin\Marketplace}\*`.
- Tables préfixées `marketplace_` / `vendor_`. Migrations `..._000024` →
  `..._000033`.
- Tests : `tests/Feature/Marketplace/*` (fondations, onboarding, catalogue,
  workflow d'offre, boutique, checkout, règlement, traitement/avis).

---

## 11. Garde-fous « ne rien casser »

- Aucune colonne existante supprimée / renommée ; seulement de nouvelles tables.
- Routes et `names` existants intacts (`marketplace.*`, `vendor.*`,
  `admin.marketplace.*` sont nouveaux).
- Moteur d'affiliation non modifié — le chemin marketplace n'appelle jamais
  `AffiliationService`.
- Contrats `PaymentGateway` / `PayoutGateway` réutilisés sans modification.
- Table `users` : seulement une nouvelle valeur possible pour `role` +
  `vendor_profiles`. Flux d'authentification inchangé.
