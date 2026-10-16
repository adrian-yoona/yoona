# Wyniki sprawdzenia Yoona SEO 2.0.0

Data: 16 października 2026 r. Testy wykonano na odizolowanym WordPressie, bez połączenia z panelem lub bazą produkcyjną yoona.pl.

## Potwierdzone zachowanie

- **17 testów Pythona**: analiza eksportu, parser audytu i rozpoznanie blokady sieciowej. Nie są to pomiary działającej witryny.
- Kontrola składni wszystkich plików PHP oraz **90 asercji integracyjnych WordPressa**: uprawnienia, nonce, import, kontrola stanu przed zapisem, kopie, konflikty, blokada równoległych wdrożeń i odzyskiwanie po błędzie.
- **59 asercji helpera Divi**: wybór modułów tekstowych i odpowiedzi FAQ, hashe, ochrona HTML, odrzucanie shortcode w nowych tekstach i zachowanie pozostałych bajtów źródła. Obejmują także nawiasy w klasach CSS znalezione w rzeczywistym eksporcie.
- **37 asercji Divi i natywnych pól Rank Math**: wspólny zapis tekstów/metadanych, podgląd, cofanie, zachowanie struktury i wykrywanie już zapisanych wartości.
- **30 asercji rzeczywistego HTTP bez Rank Math i 31 z aktywnym Rank Math**: logowanie, panel, wysłanie JSON, nonce, nieaktualna paczka, limity partii, zapis i cofanie. Wynikowy HTML zawiera właściwy tytuł i pojedynczy opis.
- Osobny test natywnego zapisu: metadane pozostają poprawne w wynikowym HTML po wyłączeniu Yoona SEO, a ponowne włączenie umożliwia dokładne cofnięcie wartości. Wcześniejszy test symulowanych filtrów Yoast nie jest testem instalacji Yoast SEO.
- Wykonanie skryptu panelu w Node.js: **200 pozycji w 20 kolejnych partiach**, ochrona przed podwójnym wysłaniem i zatrzymanie po błędzie. Drugi scenariusz z natywnym FormData sprawdza podział 25 pozycji na 10/10/5, pomijanie wyłączonych pól i zachowanie postępu przy błędzie.
- ZIP: sześć dozwolonych plików, poprawne CRC i zgodność bajt po bajcie z kodem źródłowym. Oba opublikowane archiwa ZIP zawierają tę samą wersję 2.0.0.

## Test całej rzeczywistej paczki

Prywatny eksport właściciela odtworzono w oddzielnych tabelach bazy, niedostępnych z testowego serwera WWW. **3529 asercji** obejmuje wszystkie **200 pozycji**, **896 poprawek modułów w 56 elementach**, zapis w 20 partiach, ponowny podgląd bez ponownego zapisu, dostępność wszystkich 20 kopii w panelu oraz dokładne cofnięcie całości.

Surowego eksportu nie umieszczono w repozytorium ani w ZIP-ie. Porównania obejmują zachowanie wszystkich fragmentów Divi poza wskazanymi tekstami oraz obecność i poprzednie wartości zmienianych metadanych.

## Środowisko i odtworzenie

Testowany zestaw: **WordPress 7.1.3, PHP 8.3.35, MariaDB 11.4, Rank Math 1.0.280**. Obrazy testowe są przypięte do digestów w skrypcie. Minimalne zadeklarowane wersje WordPress 6.0 / PHP 7.4 nie były osobno uruchamiane.

Rank Math pochodzi z oficjalnego repozytorium, commit `50fc59587112316ad754ca3c9a26e1282795d49d`. Jego checkout zawierał nieaktualną wygenerowaną mapę klas. W jednorazowej kopii testowej odbudowano ją oficjalnym Composerem, bez sieci, instalowania zależności, skryptów i wtyczek Composera; kod źródłowy i lockfile pozostały bez zmian. Test używa obsługiwanej opcji pominięcia rejestracji konta. Nie jest to test dokładnego archiwum wydania Rank Math z katalogu WordPressa.

Pełny wynik: [wordpress-integration-results.txt](../tests/wordpress-integration-results.txt). Instrukcje odtworzenia i izolacji danych: [tests/README.md](../tests/README.md). Kontenery, wolumen i sieć utworzone przez zakończony przebieg są usuwane; obrazy mogą pozostać do kolejnych testów.

## Paczka do pobrania

Plik: [downloads/yoona-seo-v2.zip](../downloads/yoona-seo-v2.zip)

SHA-256 ZIP-a:

```text
ab4b279fbc51cec1f7815ac35fb9e2dd5f01d682ab43a3a31d9363de7f30acad
```

SHA-256 dołączonego `content/changes.json`:

```text
e97c45484286479ed153ab88fc7000231e1607b40f1a5d71df51f9c3401c1f85
```

## Granice potwierdzenia

Nie wykonano wdrożenia na yoona.pl. Aktualizacja lub aktywacja wtyczki nie zapisuje poprawek; właściciel uruchamia zapis w jej panelu.

Nie uruchomiono licencjonowanego motywu Divi. Zachowanie shortcode sprawdzano parserem WordPressa i testowymi callbackami; nie potwierdza to wyglądu działającego motywu. Po wdrożeniu trzeba sprawdzić widok komputerowy i mobilny, galerie oraz formularze.

Filtr sieciowy środowiska blokował odczyt yoona.pl. Audyt obejmuje pełny otrzymany eksport, bez pomiarów produkcyjnego HTML, indeksacji, szybkości, Core Web Vitals, Search Console lub ruchu. Testy nie dowodzą wzrostu pozycji w Google.
