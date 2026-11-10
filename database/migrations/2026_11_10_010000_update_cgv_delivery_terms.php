<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Align existing editable CGV copy with the new shipping policy.
 * Only change the known legacy sentence, preserving all other admin edits.
 */
return new class extends Migration
{
    public function up(): void
    {
        $page = DB::table('pages')
            ->whereRaw("slug->>'fr' = ?", ['conditions-generales-de-vente'])
            ->whereNull('deleted_at')
            ->first(['id', 'body']);

        if (! $page) {
            return;
        }

        $body = json_decode((string) $page->body, true);
        if (! is_array($body) || ! is_string($body['fr'] ?? null)) {
            return;
        }

        $old = 'Les délais de livraison sont estimés entre 5 et 7 jours ouvrés.';
        if (! str_contains($body['fr'], $old)) {
            return;
        }

        $new = 'Le délai de livraison standard annoncé est de 3 à 5 jours ouvrés, selon les modalités de décompte précisées dans nos <a href="/livraison">conditions de livraison</a>. Si un autre délai s’applique en raison de la destination ou du transporteur choisi, il vous est indiqué avant la validation de votre commande.';
        $body['fr'] = str_replace($old, $new, $body['fr']);

        DB::table('pages')->where('id', $page->id)->update([
            'body' => json_encode($body, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'updated_at' => now(),
        ]);
    }

    public function down(): void
    {
        // Leave published legal content intact; it may have since been edited in Filament.
    }
};
