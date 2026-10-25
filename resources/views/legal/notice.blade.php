@extends('legal.layout')

@php
    $c = config('malkuthar.contact');
    $l = config('malkuthar.legal');
    $todo = '<em class="not-italic text-rust">À compléter</em>';
    $val = fn ($v) => filled($v) ? e($v) : $todo;
@endphp

@section('title', 'Mentions légales')
@section('seo_description', 'Mentions légales du site MALKUTHAR : éditeur, hébergeur, propriété intellectuelle et contact.')
@section('legal_heading', 'Mentions légales')
@section('legal_intro', 'Informations sur l\'éditeur et l\'hébergeur du site ' . parse_url(config('app.url'), PHP_URL_HOST) . '.')

@section('legal_body')
    <section>
        <h2>1. Éditeur du site</h2>
        <ul>
            <li><strong>Raison sociale :</strong> {{ $c['company'] }}</li>
            <li><strong>Forme juridique :</strong> {!! $val($l['legal_form']) !!}</li>
            <li><strong>Capital social :</strong> {!! $val($l['capital']) !!}</li>
            <li><strong>Siège social :</strong> {{ $c['address'] }}</li>
            <li><strong>RCCM :</strong> {!! $val($l['rccm']) !!}</li>
            <li><strong>IFU :</strong> {!! $val($l['ifu']) !!}</li>
            <li><strong>E-mail :</strong> <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a></li>
            <li><strong>Téléphone :</strong> {!! $val($c['phone']) !!}</li>
            <li><strong>Directeur de la publication :</strong> {!! $val($l['publication_director']) !!}</li>
        </ul>
    </section>

    <section>
        <h2>2. Hébergement</h2>
        <ul>
            <li><strong>Hébergeur :</strong> {!! $val($l['host_name']) !!}</li>
            <li><strong>Adresse :</strong> {!! $val($l['host_address']) !!}</li>
        </ul>
    </section>

    <section>
        <h2>3. Activité</h2>
        <p>
            MALKUTHAR vend en ligne des produits digitaux (ebooks, formations, audios), des services
            sur rendez-vous et des produits physiques. Le site héberge aussi une place de marché sur
            laquelle des vendeurs indépendants proposent leurs propres offres, sous leur responsabilité ;
            leurs conditions sont précisées dans les <a href="{{ route('marketplace.vendor-terms') }}">conditions vendeurs</a>.
            Le <a href="{{ route('affiliate.program-terms') }}">programme d'affiliation</a> fait l'objet de conditions propres.
        </p>
    </section>

    <section>
        <h2>4. Paiements</h2>
        <p>
            Les paiements par Mobile Money et carte bancaire sont traités par des prestataires de
            paiement agréés (FedaPay, CinetPay). MALKUTHAR ne reçoit ni ne conserve les numéros de carte bancaire.
        </p>
    </section>

    <section>
        <h2>5. Propriété intellectuelle</h2>
        <p>
            Les textes, images, logos, vidéos et contenus digitaux du site sont protégés par le droit
            d'auteur et appartiennent à {{ $c['company'] }} ou à leurs auteurs respectifs. Toute
            reproduction, diffusion ou revente, totale ou partielle, sans autorisation écrite est interdite.
            L'achat d'un produit digital donne un droit d'usage personnel, non transférable.
        </p>
    </section>

    <section>
        <h2>6. Responsabilité</h2>
        <p>
            MALKUTHAR s'efforce de fournir des informations exactes et à jour, sans pouvoir garantir
            l'absence d'erreur. Les liens vers d'autres sites sont fournis à titre indicatif ;
            MALKUTHAR n'est pas responsable de leur contenu.
        </p>
    </section>

    <section>
        <h2>7. Données personnelles et cookies</h2>
        <p>
            Le traitement de vos données est décrit dans la <a href="{{ route('legal.privacy') }}">politique de confidentialité</a>,
            et l'usage des cookies dans la page <a href="{{ route('legal.cookies') }}">gestion des cookies</a>.
        </p>
    </section>

    <section>
        <h2>8. Droit applicable</h2>
        <p>
            Les présentes mentions sont régies par le droit béninois. En cas de litige, et à défaut de
            solution amiable, les tribunaux compétents de Cotonou seront saisis.
        </p>
    </section>

    <section>
        <h2>9. Contact</h2>
        <p>
            Pour toute question : <a href="{{ route('contact') }}">formulaire de contact</a> ou
            <a href="mailto:{{ $c['email'] }}">{{ $c['email'] }}</a>.
        </p>
    </section>
@endsection
