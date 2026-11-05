<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Cookies policy: consent banner is now vanilla-cookieconsent (cookie `cc_cookie`),
 * not Cookie-Script. Updates the already-inserted page text in place via exact
 * string replacement, so any other admin edits are preserved.
 */
return new class extends Migration
{
    public function up(): void
    {
        $this->swap(false);
    }

    public function down(): void
    {
        $this->swap(true);
    }

    private function swap(bool $reverse): void
    {
        $row = DB::table('pages')
            ->whereRaw("slug->>'fr' = ?", ['politique-de-cookies'])
            ->whereNull('deleted_at')
            ->first();

        if (! $row) {
            return;
        }

        $body = json_decode((string) $row->body, true) ?: [];
        $fr = (string) ($body['fr'] ?? '');

        foreach ($this->pairs() as [$old, $new]) {
            $fr = $reverse ? str_replace($new, $old, $fr) : str_replace($old, $new, $fr);
        }

        $body['fr'] = $fr;

        DB::table('pages')->where('id', $row->id)->update([
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    /** @return array<int, array{0: string, 1: string}> */
    private function pairs(): array
    {
        return [
            [
<<<'OLD0'
            <td><code>CookieScriptConsent</code></td>
            <td>Cookie-Script.com (outil de gestion du consentement)</td>
OLD0,
<<<'NEW0'
            <td><code>cc_cookie</code></td>
            <td>BLACK MILK</td>
NEW0,
            ],
            [
<<<'OLD1'
onclick="if (window.CookieScript && window.CookieScript.instance) { window.CookieScript.instance.show(); } return false;"
OLD1,
<<<'NEW1'
data-cc="show-preferencesModal"
NEW1,
            ],
            [
<<<'OLD2'
<p>D’autres traceurs ne sont déposés ou lus qu’<strong>après votre accord</strong> : mesure d’audience et de performance, personnalisation de l’expérience, contenus ou publicités ciblés. Aucun de ces traceurs n’est activé tant que vous n’avez pas fait votre choix.</p>
OLD2,
<<<'NEW2'
<p>D’autres traceurs ne sont déposés ou lus qu’<strong>après votre accord</strong>. Ils relèvent de la catégorie « cookies marketing » : mesure de l’efficacité de nos campagnes publicitaires et affichage d’offres adaptées à vos centres d’intérêt (par exemple via Meta Pixel ou des plateformes publicitaires). Aucun de ces traceurs n’est activé tant que vous n’avez pas fait votre choix.</p>
NEW2,
            ],
            [
<<<'OLD3'
<li>Lors de votre première visite, un bandeau vous permet d’<strong>accepter</strong>, de <strong>refuser</strong> ou de <strong>personnaliser</strong> vos choix par catégorie ; refuser est aussi simple qu’accepter.</li>
OLD3,
<<<'NEW3'
<li>Lors de votre première visite, un bandeau vous permet de <strong>tout accepter</strong>, de <strong>tout refuser</strong> ou de <strong>gérer vos préférences</strong> par catégorie ; refuser est aussi simple qu’accepter.</li>
NEW3,
            ],
            [
<<<'OLD4'
    <li>La liste détaillée et à jour de ces traceurs (nom, émetteur, finalité, durée) est consultable à tout moment dans le centre de préférences du bandeau.</li>
OLD4,
<<<'NEW4'
    <li>Cette politique est mise à jour chaque fois qu’un nouveau traceur soumis à consentement est ajouté au site.</li>
NEW4,
            ],
        ];
    }
};
