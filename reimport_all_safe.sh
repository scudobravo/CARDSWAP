#!/bin/bash

# Script per re-importare tutti i CSV in modo sicuro
# Questo script:
# 1. Fa un backup del database
# 2. Elimina solo le carte senza inserzioni attive
# 3. Re-importa tutti i CSV
# 4. Preserva le carte degli utenti con inserzioni attive

set -e

echo "🚀 Re-importazione sicura di tutti i CSV"
echo "═══════════════════════════════════"
echo ""

# Colori per output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Directory di lavoro
cd "$(dirname "$0")"

# 1. Backup del database
echo -e "${YELLOW}📦 Step 1: Backup del database...${NC}"
BACKUP_FILE="/home/forge/backup_before_reimport_$(date +%Y%m%d_%H%M%S).sql"
DB_USER=$(php artisan tinker --execute="echo config('database.connections.mysql.username');" 2>/dev/null | grep -v "Warning" | tail -1)
DB_PASS=$(php artisan tinker --execute="echo config('database.connections.mysql.password');" 2>/dev/null | grep -v "Warning" | tail -1)
DB_NAME=$(php artisan tinker --execute="echo config('database.connections.mysql.database');" 2>/dev/null | grep -v "Warning" | tail -1)

if [ -n "$DB_USER" ] && [ -n "$DB_NAME" ]; then
    if [ -n "$DB_PASS" ]; then
        mysqldump -u "$DB_USER" -p"$DB_PASS" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null && {
            echo -e "${GREEN}✅ Backup completato: $BACKUP_FILE${NC}"
        } || {
            echo -e "${YELLOW}⚠️  Backup automatico fallito, continua senza backup...${NC}"
        }
    else
        mysqldump -u "$DB_USER" "$DB_NAME" > "$BACKUP_FILE" 2>/dev/null && {
            echo -e "${GREEN}✅ Backup completato: $BACKUP_FILE${NC}"
        } || {
            echo -e "${YELLOW}⚠️  Backup automatico fallito, continua senza backup...${NC}"
        }
    fi
else
    echo -e "${YELLOW}⚠️  Impossibile recuperare credenziali DB, continua senza backup...${NC}"
fi
echo ""

# 2. Conta carte con inserzioni attive
echo -e "${YELLOW}📊 Step 2: Verifica carte con inserzioni attive...${NC}"
ACTIVE_CARDS=$(php artisan tinker --execute="
\$count = \App\Models\CardModel::whereHas('cardListings', function(\$q) {
    \$q->where('status', 'active');
})->count();
echo \$count;
" 2>/dev/null | grep -E '^[0-9]+$' || echo "0")

echo -e "${GREEN}✅ Carte con inserzioni attive: $ACTIVE_CARDS${NC}"
echo ""

if [ "$ACTIVE_CARDS" -gt 0 ]; then
    echo -e "${YELLOW}⚠️  ATTENZIONE: Ci sono $ACTIVE_CARDS carte con inserzioni attive che verranno preservate${NC}"
    echo ""
fi

# 3. Salva gli ID delle carte con inserzioni attive prima dell'eliminazione
echo -e "${YELLOW}💾 Step 3a: Salvataggio ID carte con inserzioni attive...${NC}"
SAVED_IDS=$(php artisan tinker --execute="
\$ids = \App\Models\CardModel::whereHas('cardListings', function(\$q) {
    \$q->where('status', 'active');
})->pluck('id')->toArray();
echo implode(',', \$ids);
" 2>/dev/null | grep -E '^[0-9,]*$' || echo "")

if [ -n "$SAVED_IDS" ]; then
    echo -e "${GREEN}✅ ID carte salvati: $SAVED_IDS${NC}"
else
    echo -e "${YELLOW}⚠️  Nessuna carta con inserzioni attive trovata${NC}"
fi
echo ""

# 3b. Elimina solo le carte senza inserzioni attive
echo -e "${YELLOW}🗑️  Step 3b: Eliminazione carte senza inserzioni attive...${NC}"
php artisan tinker --execute="
DB::statement('SET FOREIGN_KEY_CHECKS=0;');
\$deleted = \App\Models\CardModel::whereDoesntHave('cardListings', function(\$q) {
    \$q->where('status', 'active');
})->delete();
DB::statement('SET FOREIGN_KEY_CHECKS=1;');
echo 'Carte eliminate: ' . \$deleted;
" 2>/dev/null | grep "Carte eliminate"
echo -e "${GREEN}✅ Carte eliminate${NC}"
echo ""

# 4. Re-importazione CSV
echo -e "${YELLOW}📥 Step 4: Re-importazione CSV...${NC}"
echo ""

# Elenco1.csv
if [ -f "TOIMPORT/Elenco1.csv" ]; then
    echo -e "${YELLOW}📄 Importazione Elenco1.csv...${NC}"
    php artisan import:football-excel-cards --file=TOIMPORT/Elenco1.csv
    echo -e "${GREEN}✅ Elenco1.csv importato${NC}"
    echo ""
else
    echo -e "${RED}❌ File TOIMPORT/Elenco1.csv non trovato${NC}"
fi

# Elenco2.csv
if [ -f "TOIMPORT/Elenco2.csv" ]; then
    echo -e "${YELLOW}📄 Importazione Elenco2.csv...${NC}"
    php artisan import:football-excel-cards --file=TOIMPORT/Elenco2.csv
    echo -e "${GREEN}✅ Elenco2.csv importato${NC}"
    echo ""
else
    echo -e "${RED}❌ File TOIMPORT/Elenco2.csv non trovato${NC}"
fi

# Altri CSV (basketball, spongebob, disney, etc.)
if [ -f "TOIMPORT/Elenco Set Basket 1 - Foglio1.csv" ]; then
    echo -e "${YELLOW}📄 Importazione Elenco Set Basket 1 - Foglio1.csv...${NC}"
    php artisan import:basket-cards --file="TOIMPORT/Elenco Set Basket 1 - Foglio1.csv"
    echo -e "${GREEN}✅ Basket cards importate${NC}"
    echo ""
fi

# Aggiungi altri CSV qui se necessario

# 5. Verifica risultati e preservazione carte utenti
echo -e "${YELLOW}📊 Step 5: Verifica risultati...${NC}"
php artisan tinker --execute="
\$total = \App\Models\CardModel::count();
\$withNumbered = \App\Models\CardModel::whereNotNull('card_number_in_set')->count();
\$withoutName = \App\Models\CardModel::where(function(\$q) {
    \$q->whereNull('name')->orWhere('name', '');
})->count();
\$withActiveListings = \App\Models\CardModel::whereHas('cardListings', function(\$q) {
    \$q->where('status', 'active');
})->count();

echo 'Carte totali: ' . \$total . PHP_EOL;
echo 'Carte con card_number_in_set: ' . \$withNumbered . PHP_EOL;
echo 'Carte senza nome: ' . \$withoutName . PHP_EOL;
echo 'Carte con inserzioni attive: ' . \$withActiveListings . PHP_EOL;
" 2>/dev/null

echo ""

# 5b. Verifica che le carte degli utenti siano ancora presenti
if [ -n "$SAVED_IDS" ]; then
    echo -e "${YELLOW}🔍 Step 5b: Verifica preservazione carte utenti...${NC}"
    PRESERVED=$(php artisan tinker --execute="
    \$ids = explode(',', '$SAVED_IDS');
    \$preserved = 0;
    foreach (\$ids as \$id) {
        if (\App\Models\CardModel::find(\$id)) {
            \$preserved++;
        }
    }
    echo \$preserved . '/' . count(\$ids);
    " 2>/dev/null | grep -E '^[0-9]+/[0-9]+$' || echo "0/0")
    
    if [ "$PRESERVED" != "0/0" ]; then
        echo -e "${GREEN}✅ Carte utenti preservate: $PRESERVED${NC}"
    else
        echo -e "${RED}❌ ERRORE: Carte utenti non trovate!${NC}"
        exit 1
    fi
    echo ""
fi

echo ""
echo -e "${GREEN}✅ Re-importazione completata!${NC}"
echo "═══════════════════════════════════"

