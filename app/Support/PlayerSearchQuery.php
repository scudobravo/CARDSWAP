<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;

/**
 * Ricerca nomi giocatore con separatori flessibili: spazi, "/" e "," equivalgono.
 * Es. "kaka ronaldo" trova "Kaká / Ronaldo" perché ogni token deve comparire nel nome (AND).
 */
class PlayerSearchQuery
{
    /**
     * @return list<string>
     */
    public static function nameSearchTokens(?string $raw): array
    {
        if ($raw === null) {
            return [];
        }
        $raw = trim($raw);
        if ($raw === '') {
            return [];
        }

        $parts = preg_split('/[\s\/,]+/u', $raw, -1, PREG_SPLIT_NO_EMPTY);
        if ($parts === false) {
            return [$raw];
        }
        $parts = array_values(array_map('trim', $parts));

        if ($parts === []) {
            return [$raw];
        }

        return $parts;
    }

    public static function escapeLikeWildcards(string $value): string
    {
        return str_replace(['\\', '%', '_'], ['\\\\', '\%', '\_'], $value);
    }

    /**
     * Applica più condizioni LIKE in AND sulla colonna (es. name).
     */
    public static function wherePlayerNameMatches(Builder $query, string $column, ?string $rawSearch): void
    {
        $tokens = self::nameSearchTokens($rawSearch);
        foreach ($tokens as $token) {
            if ($token === '') {
                continue;
            }
            $escaped = self::escapeLikeWildcards($token);
            $query->where($column, 'LIKE', '%'.$escaped.'%');
        }
    }

    /**
     * Ordina i risultati dell'autocomplete quando c'è testo di ricerca:
     * prima nomi "semplici" (senza più giocatori separati da "/"), poi i più corti,
     * infine alfabetico — così "Ronaldo" non resta fuori dal limite a favore di stringhe lunghe tipo "Pele / Cristiano Ronaldo / ...".
     */
    public static function orderPlayerNameForAutocomplete(Builder $query, ?string $rawSearch): void
    {
        if ($rawSearch === null || trim($rawSearch) === '') {
            $query->orderBy('name');

            return;
        }

        $query->orderByRaw('CASE WHEN name NOT LIKE ? THEN 0 ELSE 1 END', ['%/%']);

        $driver = $query->getConnection()->getDriverName();
        if ($driver === 'sqlite') {
            $query->orderByRaw('length(name) ASC');
        } else {
            $query->orderByRaw('CHAR_LENGTH(name) ASC');
        }

        $query->orderBy('name');
    }
}
