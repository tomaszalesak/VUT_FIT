# Implementační dokumentace k 1. úloze do IPP 2018/2019
* Jméno a příjmení: Tomáš Zálešák
* Login: xzales13

## Parsování IPPcode19

Načítám řádek po řádku a postupně zpracovávám.

Generátorová funkce `getLine` vrací řádek s instrukcí s odstraněným komentářem. Všechny přebytečné bílé znaky odstraní a nechá přesně jednu mezeru mezi viditelnými znaky.

Funkce `parse` vytvoří nový DOMDocument a zapíše úvodní XML informace. Dále pomocí foreach projdu popořadě všechny řádky získané přes `getLine` s instrukcemi a pokud budou obsahovat validní instrukci, tak vygeneruji XML element.

Každý řádek s instrukcí kontroluji pomocí délky pole a regulárních výrazů. 

Generace XML elementů instrukcí dělím podle typů argumentů. Pro každý typ mám jednu funkci, kterou zavolám.

Získané XML vypíšu na standardní výstup `fwrite(STDOUT, $dom->saveXML());`.

## Ošetření chyb

Při chybách se vyhazuje `ParseException`, které se předá zpráva a chybový kód. Konstruktor třídy rovnou vypíše informaci o chybě na chybový výstup. Pak se program ukončí s předaným chybovým kódem.

## Zpracování a předání argumentů

Implementace zpracování argumentů se nachází ve třídě `ParseArguments`. Případné argumenty jsou uloženy jako statické proměnné.

Funkce `getArguments` spustí analýzu argumentů, jejich kontrolu, nastavení statických proměných, či případné vyhození chyby `ParseException` při nesprávném zadání argumentů. Argumenty prochází jeden po druhém v poli `argv` pomocí cyklu`foreach`.

## Výpis pomoci

Pokud je přítomen argument `--help`, funkce `help()` vypíše informace.


