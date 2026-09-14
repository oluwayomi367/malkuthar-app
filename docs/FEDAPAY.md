# FedaPay — intégration & test (sandbox)

Réf. : https://docs.fedapay.com/introduction/fr/fedapay-fr

L'intégration est **déjà en place** dans le code :

| Élément | Fichier |
|---|---|
| Contrat commun PSP | `app/Contracts/PaymentGateway.php` |
| Implémentation FedaPay | `app/Services/Payments/FedaPayGateway.php` |
| Enveloppe du SDK `fedapay/fedapay-php` | `app/Services/Payments/FedaPayClient.php` |
| Résolveur de PSP | `app/Services/Payments/PaymentGatewayManager.php` (`fedapay` → `FedaPayGateway`) |
| Initiation du paiement | `app/Services/Payments/PaymentInitiationService.php` |
| Réception webhook | `POST /webhooks/fedapay` → `PaymentWebhookController` → `ProcessPaymentWebhook` |
| Config | `config/services.php` → `services.fedapay.*` |

Flux : `createCheckoutSession()` crée une transaction (`POST /v1/transactions`)
puis un token (`POST /v1/transactions/{id}/token`) et renvoie l'`url` de
paiement. Le client paie sur la page FedaPay. FedaPay appelle ensuite le
webhook ; on **re-vérifie le statut réel via l'API** (`verifyTransaction`),
jamais le corps du webhook, puis on confirme la commande (idempotent).

## 1. Clés de test

Dashboard FedaPay en **mode Test** → *Paramètres → API* :

```
FEDAPAY_PUBLIC_KEY=pk_sandbox_xxx
FEDAPAY_SECRET_KEY=sk_sandbox_xxx
FEDAPAY_ENVIRONMENT=sandbox
```

Le SDK route alors vers `https://sandbox-api.fedapay.com/v1`.
`FEDAPAY_ENVIRONMENT=live` → `https://api.fedapay.com/v1`.

## 2. Webhook

Dashboard (mode Test) → *Webhooks* → créer un endpoint :

```
URL     : https://VOTRE_DOMAINE/webhooks/fedapay
Events  : transaction.approved, transaction.canceled, transaction.declined
```

Copier le **secret du endpoint** (`wh_...`) dans :

```
FEDAPAY_WEBHOOK_SECRET=wh_sandbox_xxx
```

La signature est vérifiée par le SDK (`\FedaPay\Webhook::constructEvent`) :
HMAC-SHA256 de `"{timestamp}.{payload}"`, en-tête `X-FEDAPAY-SIGNATURE`
(format `t=...,s=...`), tolérance temporelle anti-rejeu.

En local, exposer le port avec un tunnel (`ngrok http 8000`, `cloudflared`)
et mettre l'URL publique dans l'endpoint webhook.

## 3. Tester un paiement

1. `php artisan serve` (ou via le tunnel).
2. Ajouter un produit au panier → `/panier` → payer avec **FedaPay**.
3. On est redirigé vers la page de paiement sandbox FedaPay ; utiliser les
   moyens de test de FedaPay (Mobile Money test / carte test selon le
   compte).
4. Après paiement, FedaPay redirige vers `callback_url`
   (`/checkout/return/{order}`) **et** appelle le webhook → la commande
   passe `paid`, les commissions d'affiliation sont distribuées, les
   produits digitaux livrés.

## 4. Tester SANS FedaPay (dev / CI)

- **Tests automatisés** : `TestCase::setUp()` bind un `FakePaymentGateway`
  → aucun appel réseau (`tests/Feature/Payments/FedaPayFlowTest.php`).
- **Manuel en local** (`APP_ENV=local`) : la page `/checkout/return/{order}`
  affiche « Simuler le paiement » / « Simuler un remboursement »
  (`routes/web.php` → `dev.confirm-payment` / `dev.refund`).

## Notes

- Montant envoyé en **unité entière** de la devise (XOF sans centimes).
- `custom_metadata.order_id` sert de clé de réconciliation ; à défaut, la
  référence de transaction est aussi stockée dans `orders.payment_reference`.
- Ne jamais committer les clés : elles vivent dans `.env` (déjà git-ignoré).
