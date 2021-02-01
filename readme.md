## **Dokumentácia ku implementácií 1. úlohe IPP 2019/2020**
Implementačná dokumentácia k 1. úlohe do IPP 2019/2020 
Meno a priezvisko: Daniel Paul 
Login: xpauld00
 
## Analyzátor kódu IPPcode20
Analyzátor kódu IPPcode20 je implementovaný v súbore *parse.php*
Jeho implementácia je založená na čítaní riadkov zo vstupu a ich následnej (lexikálnej a syntaktickej) analýze pomocou regulárnych výrazov. Skript vo funkcií **argsToXML** na základe inštrukcie vytvára reprezentáciu časti kódu vo formáte XML s príslušnými argumentami, ktoré sa generujú v príslušných funkciach podľa ich typu:
- function **varToXML** - vytvára XML pre typ *var*
- function  **symbToXML** - vytvára XML pre typ *symbol*
- function  **process_symb** - kontroluje lexikálnu a syntaktickú správnosť symbolu
- function  **label_jump_ToXML** - vytvára XML pre typ *label*
- function  **typeToXML** - vytvára XML pre typ *type*

Zároveň sa pri každej inštrukcií kontroluje aj správny počet argumentov vo funkcií function  **checkArgNum**.

## Použitie
Pomôcka pre prácu so skriptom je implementovaná pomocou kontrolovania argumentu ($argv[1])  a za zobrazí pomocou argumentu -\-help, tj.
_php parse.php -\-help_
Ak pomôcku použijete nesprávne, ukončí skript sa ukončí s chybou 10.


