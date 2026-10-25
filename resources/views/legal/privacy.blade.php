@extends('legal.layout')

@php
    $c = config('malkuthar.contact');
    $refDays = config('malkuthar.affiliation.referral_cookie_days');
@endphp

@section('title', 'Politique de confidentialité')
@section('seo_description', 'Comment MALKUTHAR collecte, utilise et protège vos données personnelles, et comment exercer vos droits.')
@section('legal_heading', 'Politique de confidentialité')
@section('legal_intro', 'Cette page explique quelles données nous collectons, pourquoi, combien de temps nous les gardons et comment exercer vos droits.')

@section('legal_body')
    <section>
        <h2>1. Responsable du traitement</h2>
        <p>
            {{ $c['company'] }}, {{ $c['address'] }}, est responsable du traitement des données
            collectées sur ce site. Contact : <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a>.
        </p>
    </section>

    <section>
        <h2>2. Données collectées</h2>
        <ul>
            <li><strong>Compte :</strong> prénom, nom, adresse e-mail, téléphone (facultatif), pays, mot de passe (stocké chiffré).</li>
            <li><strong>Commandes :</strong> produits achetés, montants, adresse de livraison pour les produits physiques, rendez-vous réservés.</li>
            <li><strong>Paiement :</strong> référence et statut de la transaction transmis par le prestataire de paiement. Les numéros de carte ne nous sont jamais transmis.</li>
            <li><strong>Affiliation :</strong> code de parrainage, filleuls, commissions, moyens de retrait (par exemple numéro Mobile Money).</li>
            <li><strong>Vendeurs :</strong> informations sur la boutique et l'activité, offres publiées, ventes et versements.</li>
            <li><strong>Contact :</strong> les messages envoyés via le formulaire de contact.</li>
            <li><strong>Technique :</strong> adresse IP et journaux de connexion nécessaires à la sécurité du site.</li>
        </ul>
    </section>

    <section>
        <h2>3. Pourquoi nous les utilisons</h2>
        <ul>
            <li>Créer et gérer votre compte, traiter vos commandes, livrer vos produits et vos accès digitaux.</li>
            <li>Gérer vos réservations de services et vous envoyer les confirmations par e-mail.</li>
            <li>Calculer et verser les commissions d'affiliation et les revenus des vendeurs.</li>
            <li>Répondre à vos demandes et assurer le service client.</li>
            <li>Sécuriser le site et prévenir la fraude.</li>
            <li>Mesurer l'audience et mener des campagnes publicitaires, uniquement si ces outils sont activés (voir la page <a href="{{ route('legal.cookies') }}">cookies</a>).</li>
        </ul>
        <p>
            Ces traitements reposent sur l'exécution du contrat (vos commandes, votre compte), nos
            obligations légales (comptabilité, facturation), notre intérêt légitime (sécurité) ou votre
            consentement (mesure d'audience et publicité).
        </p>
    </section>

    <section>
        <h2>4. Qui reçoit vos données</h2>
        <ul>
            <li>Les équipes de MALKUTHAR habilitées, pour leurs missions.</li>
            <li>Les prestataires de paiement (FedaPay, CinetPay), pour traiter vos paiements.</li>
            <li>Les vendeurs de la place de marché, pour les seules commandes qui les concernent (nom, coordonnées, adresse de livraison).</li>
            <li>Notre hébergeur et notre service d'envoi d'e-mails, pour faire fonctionner le site.</li>
            <li>Les autorités, lorsque la loi l'exige.</li>
        </ul>
        <p>Nous ne vendons pas vos données.</p>
    </section>

    <section>
        <h2>5. Durée de conservation</h2>
        <ul>
            <li><strong>Compte :</strong> tant qu'il est actif, puis 3 ans après la dernière activité.</li>
            <li><strong>Commandes et factures :</strong> la durée imposée par les obligations comptables et fiscales.</li>
            <li><strong>Messages de contact :</strong> le temps de traiter la demande, puis 1 an.</li>
            <li><strong>Cookie de parrainage :</strong> {{ $refDays }} jours.</li>
        </ul>
    </section>

    <section>
        <h2>6. Vos droits</h2>
        <p>
            Vous pouvez demander l'accès à vos données, leur rectification, leur suppression, la
            limitation de leur traitement ou vous opposer à certains usages. Vous pouvez aussi retirer
            à tout moment un consentement donné. Une partie de vos informations est modifiable
            directement depuis votre espace client.
        </p>
        <p>
            Pour exercer vos droits, écrivez à <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a>.
            Nous répondons dans un délai d'un mois. Si vous estimez que vos droits ne sont pas respectés,
            vous pouvez saisir l'Autorité de Protection des Données à caractère Personnel (APDP) du Bénin.
        </p>
    </section>

    <section>
        <h2>7. Sécurité</h2>
        <p>
            Les échanges avec le site sont chiffrés (HTTPS), les mots de passe sont stockés sous forme
            chiffrée et l'accès aux données est limité aux personnes qui en ont besoin.
        </p>
    </section>

    <section>
        <h2>8. Modification de cette politique</h2>
        <p>
            Cette politique peut évoluer. La date de dernière mise à jour figure en haut de la page.
        </p>
    </section>
@endsection
