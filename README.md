# MALKUTHAR — Plateforme e-commerce & affiliation 3 niveaux

Plateforme e-commerce propriétaire de **MALKUTHAR SARL** : vente de produits
**digitaux**, **physiques** et de **services**, avec un module financier
d'affiliation à **3 niveaux (15 % / 10 % / 5 %)** réservé **exclusivement aux
produits digitaux déclarés affiliables**.

- Stack : **Laravel 12**, PHP 8.2+, base SQLite en dev.
- PSP : **FedaPay** (en place), **CinetPay** (prévu) — architecture multi‑PSP.
- Le wallet affilié est un **registre comptable (ledger)** : le solde est
  toujours recalculable à partir des écritures, jamais écrit à la main.

## Règle métier absolue

| Type de produit | Affiliation |
|---|---|
| `digital` **et** `is_affiliable = true` | ✅ commissions |
| `digital` non affiliable | ❌ |
| `physical` | ❌ |
| `service` | ❌ |

Les 3 taux sont **indépendants**, appliqués sur le **même** montant éligible
(figé au moment de l'achat dans `order_items.commission_base_amount`).

## Démarrage

```bash
composer install
cp .env.example .env        # si absent
php artisan key:generate
php artisan migrate:fresh --seed
php artisan test
npm install && npm run dev  # assets
php artisan serve
```

### Configuration

- `config/malkuthar.php` — taux de commission, délai de validation des
  commissions (§42), minimum/plafond/frais de retrait (§51, §57).
- `config/services.php` — blocs `fedapay` et `cinetpay` (clés via `.env` :
  `FEDAPAY_SECRET_KEY`, `FEDAPAY_WEBHOOK_SECRET`, `FEDAPAY_ENVIRONMENT`, …).

## Architecture (état actuel)

```
app/
  Models/            User, Product, Order, OrderItem, Wallet, LedgerEntry, Withdrawal
  Services/          AffiliationService, CheckoutService, DigitalDeliveryService, WithdrawalService
  Services/Payments/ FedaPayWebhookService
  Exceptions/        CommissionDistributionException, PaymentWebhookException, WithdrawalException
  Http/Controllers/  Checkout, PaymentWebhook, Withdrawal, AffiliateDashboard
database/migrations/ users + products + orders + order_items + wallets + ledger_entries + withdrawals
```

### Flux paiement → commission (§66)

```
Webhook FedaPay → vérif signature HMAC + re‑vérif API FedaPay (§26)
  → Order.payment_status = paid (idempotent, §27)
  → AffiliationService::distributeCommissions()  (ledger, statut pending, §40)
  → DigitalDeliveryService (email + liens signés, §9)
```

## Reste à faire (feuille de route)

1. **Chaîne d'affiliation bout‑en‑bout** *(en cours)* : auth, onboarding
   affilié (`MAL-AFF-xxxxxx`, code parrainage, `/ref/{code}`, tracking,
   attribution du parrain, anti auto‑parrainage), checkout renseignant
   `referring_affiliate_id`.
2. Cycle de vie des commissions : `pending → available` planifié (§42),
   reprise sur remboursement (§80).
3. Tests obligatoires §84 / §85 / §90.
4. Paiement bout‑en‑bout : interface PSP commune, initiation de paiement,
   CinetPay, téléchargement digital sécurisé.
5. Admin, audit log, stock / variantes / livraison / calendrier / coupons.
