# Testy integracyjne WordPress

Uruchom z katalogu projektu:

```bash
bash tests/record-wordpress.sh
```

Wymagane: Docker z działającym daemonem, Python 3, Node.js i Git. Skrypt pobiera oficjalne
obrazy WordPress/Apache, WP CLI, MariaDB i Composer przez standardową weryfikację Docker.
Domyślne obrazy są przypięte do digestów, aby kolejne uruchomienia korzystały
z tych samych wersji: WordPress 7.1.3, PHP 8.3.35 i MariaDB 11.4. Raport
`wordpress-integration-results.txt` jest podmieniany dopiero po pełnym sukcesie;
nieudany test zachowuje poprzedni poprawny raport.

Każde uruchomienie tworzy osobną bazę, wolumen i sieć. Nazwy zaczynają się od
`yoona-seo-test-`; port WWW jest losowy i udostępniony wyłącznie na
`127.0.0.1`. Lokalne hasła testowe nie służą do połączenia z prawdziwą stroną.
Skrypt nie łączy się z panelem ani bazą `yoona.pl`, nie zmienia plików źródłowych
wtyczki i po zakończeniu usuwa utworzone zasoby testowe. Połączenia HTTP wychodzące
z testowego WordPressa są zablokowane jego standardowym ustawieniem.

Testy sprawdzają składnię PHP, brak zmian po aktywacji i imporcie, mapowanie
adresów na strony, blokady niebezpiecznego HTML i nieaktualnych treści,
uprawnienia, konkurencyjny zapis, kopie zapasowe i odzyskiwanie po błędach.
Osobny proces symuluje obecność Yoast SEO przez stałą identyfikującą dostawcę
i sprawdza przekazywanie wartości przez filtry oraz wyłączenie dodatkowego
tagu description. Ta faza nie instaluje Yoast SEO i nie potwierdza zgodności
z każdą jego wersją; właściwy Rank Math jest uruchamiany w dalszej fazie.
Test HTTP loguje
się do lokalnego panelu, przesyła prawdziwy plik JSON, sprawdza nonce, wdraża
metadane, weryfikuje wynik na stronie i cofa zmiany. Wysyłanie JSON sprawdza limity
partii, nonce i nieaktualny podgląd. Oryginalny skrypt panelu jest dodatkowo
wykonywany w Node.js z atrapą DOM i fetch: 200 pozycji, 20 kolejnych żądań,
ochrona przed podwójnym wysłaniem i zatrzymanie przy błędzie.

W osobnej fazie uruchamiany jest prawdziwy Rank Math 1.0.280 z oficjalnego repozytorium
Git, przypięty do commitu `50fc59587112316ad754ca3c9a26e1282795d49d`. Można wskazać
gotowy checkout przez `YOONA_TEST_RANK_MATH_SOURCE`. W jego jednorazowej kopii
testowej Composer odbudowuje nieaktualną mapę klas, bez pobierania zależności,
wykonywania skryptów i uruchamiania wtyczek Composera. Używana jest obsługiwana przez
Rank Math opcja pominięcia rejestracji konta. Test sprawdza zapis do jego własnych
pól SEO, cofanie dokładnej obecności metadanych i wynik HTML również po wyłączeniu
Yoona SEO.

Test Divi sprawdza zachowanie oryginalnych bajtów shortcode, atrybutów, obrazów,
galerii i formularzy poza zmienianymi fragmentami tekstu. Parser WordPressa jest
uruchamiany z testowymi callbackami. Licencjonowany motyw Divi nie jest dołączony;
te testy nie zastępują wizualnego przeglądu strony z tym motywem.

Warstwa wyglądu ma osobne testy na rzeczywistych żądaniach HTTP: nowa strona
główna, podstrona z zachowaną treścią Divi, wpis, archiwum, wyszukiwanie, błąd
404 i formularze ochrony hasłem. Testy sprawdzają standardowe hooki WordPressa,
pojedyncze tytuły i opisy SEO, wyłączenie wyglądu dla RSS, REST, osadzania
i edytora Divi, a także włączanie i wyłączanie przez formularze administratora.
Przed i po samych operacjach wyglądu porównywane są wszystkie rekordy wpisów
oraz metadanych; zmiana szablonu nie może ich modyfikować. Przed zapisem stanu
początkowego oryginalny motyw blokowy jest raz renderowany, aby WordPress
utworzył własny rekord nawigacji. Porównanie nadal obejmuje wszystkie rekordy.

Opcjonalny test w prawdziwym Chromium wymaga pakietu Node `playwright`
i przeglądarki pod `/usr/bin/chromium` (ścieżkę można wskazać przez
`YOONA_TEST_CHROMIUM`). Uruchom go wraz z pełnym przebiegiem:

```bash
YOONA_TEST_BROWSER=1 bash tests/record-wordpress.sh
```

Sprawdza widok komputerowy 1440 px i mobilny 390/320 px, brak poziomego przewijania, pojedynczy
nagłówek H1, obsługę menu klawiaturą i widoczność zachowanej treści podstrony.
Zrzuty trafiają do `/tmp/yoona-design-screenshots` lub katalogu wskazanego
przez `YOONA_TEST_SCREENSHOTS`. W teście obrazki realizacji są zastępowane
oznaczonymi lokalnymi obrazkami SVG, ponieważ nie można pobrać zdjęć z yoona.pl.
Zrzuty potwierdzają układ i zachowanie przeglądarki, a nie wygląd prawdziwych
zdjęć na stronie produkcyjnej. Testowe callbacki shortcode nie zastępują
licencjonowanego motywu Divi.

Po zmianie samego CSS można ponownie uruchomić test przeglądarki na zachowanej
lokalnej instalacji testowej przez `bash tests/record-design-browser.sh ADRES`.
Plik `design-browser-results.txt` jest podmieniany atomowo dopiero po sukcesie.
Test porównuje hash serwowanego CSS z aktualnym źródłem oraz mierzy szerokości
wszystkich kart galerii na telefonach, aby wykryć również ukryte lub zbyt wąskie
kolumny. Ten osobny wynik nie zastępuje raportu testów PHP i bazy danych.

Opcjonalny test rzeczywistej paczki można uruchomić z prywatnym eksportem:

```bash
YOONA_TEST_EXPORT_JSON=/prywatna/sciezka/eksport.json bash tests/record-wordpress.sh
```

Eksport trafia wyłącznie do `/tmp` kontenera CLI, poza katalogiem HTTP. Jego
rekordy są odtwarzane w oddzielnych tabelach `yoona_real_`, których kontener WWW
nie obsługuje. Test sprawdza wszystkie poprawki, wdraża paczkę partiami po 10,
sprawdza wynik i dostępność wszystkich kopii w panelu, a następnie cofa całość.
Raport zawiera wyniki i liczby, bez źródłowej treści eksportu.

Opcjonalnie `YOONA_TEST_KEEP=1 bash tests/run-wordpress.sh` pozostawia zasoby
do diagnozy; ich prefiks i adres WWW są wypisane w wyniku. Usuń później tylko
kontenery, wolumen i sieć o tym konkretnym prefiksie.

Zmienne `YOONA_TEST_WP_IMAGE`, `YOONA_TEST_CLI_IMAGE`, `YOONA_TEST_DB_IMAGE` i `YOONA_TEST_COMPOSER_IMAGE`
pozwalają uruchomić ten sam test z innymi zgodnymi oficjalnymi obrazami.
