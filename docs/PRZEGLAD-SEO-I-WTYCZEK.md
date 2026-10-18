# Przegląd SEO, zdjęć i wtyczek YOONA

Stan przeglądu: 18 października 2026. Zakres obejmuje kod i publiczny pakiet
treści przygotowywanej wersji 3.5.0, dostarczony eksport WordPressa oraz wcześniejsze
wyniki testów lokalnych. To nie jest potwierdzenie wdrożenia na yoona.pl.
Próby pobrania produkcji blokuje proxy HTTP 403. Nie zmierzono tutaj rzeczywistych
Core Web Vitals, indeksacji Google, bieżących pozycji ani ustawień kont Google.

## Wynik przeglądu treści

Pakiet zawiera 200 różnych adresów z własnymi tytułami SEO i opisami. Wszystkie
tytuły i opisy są niepuste oraz unikalne: tytuły mają 35–59 znaków, opisy 130–158.
To kontrola redakcyjna; Google może inaczej wyświetlić lub przepisać snippet,
a szerokość wyniku zależy również od znaków i urządzenia.

47 opisów usług ma wstęp, zastosowania, materiały, proces, czynniki ceny,
checklistę wyceny i FAQ. Ich 147 odnośników do usług podrzędnych i powiązanych
prowadzi do istniejących definicji. 150 opisów realizacji ma 449 wskazań usług
lub stron nawigacji. Definicja adresu nie potwierdza jeszcze publikacji strony:
render sprawdza stan `publish` i ochronę hasłem, zanim doda odnośnik.

Powyższe liczby można odtworzyć bez prywatnego eksportu i bez połączenia z siecią:

```bash
python3 tools/audit_seo_content.py
```

Narzędzie niczego nie zapisuje i nie mierzy produkcji. Sprawdza również odrębność
zdjęć sześciu nowych ofert, ich domenę, format ścieżki i obecność opisów alt.

## Zdjęcia dla sześciu nowych ofert

Dotychczas trzy podstrony CNC dziedziczyły jedno zdjęcie głównej oferty CNC,
a trzy podstrony markiz — jedno zdjęcie głównej oferty markiz. Teraz mają
12 różnych fotografii: osobne ujęcie na górę i osobne na kartę każdej usługi.
To zdjęcia występujące w dostarczonych, wcześniej publicznych realizacjach YOONA.
Nie wygenerowano fikcyjnych realizacji ani zdjęć produktów.

| Nowa oferta | Zdjęcie na górze | Zdjęcie na karcie | Publiczne źródło |
|---|---|---|---|
| Frezowanie plexi | `litery-niepodswietlane-2-1024x768.jpg` | `litery-niepodswietlane-3-1024x768.jpg` | [Litery z plexi — poradnia](https://yoona.pl/litery-plexi-poradnia-psychologiczna/) |
| Frezowanie dibondu | `litery-frezowane-z-dibondu-olsztyn-1024x768.jpeg` | `kasetony-frezowane-yoona-olsztyn-ostroda-2-1024x768.jpg` | [OSP Długi Borek](https://yoona.pl/litery-frezowane-z-dibondu-dla-osp/), [ALIGATOR](https://yoona.pl/kasetony-frezowane-z-wystajaca-plexi-aligator-olsztyn/) |
| Frezowanie elementów reklamowych | `kaseton-led-litery-3d-z-plexi-INVESTOR-ostroda-4-1024x768.jpg` | `kasetony-frezowane-yoona-olsztyn-ostroda-6-1024x768.jpg` | [INVESTOR](https://yoona.pl/kaseton-frezowany-litery-3d-investor-ostroda/), [ALIGATOR](https://yoona.pl/kasetony-frezowane-z-wystajaca-plexi-aligator-olsztyn/) |
| Markizy tarasowe | `2026/06/markizy-olsztyn3.jpg` | `2026/06/markizy-olsztyn4.jpg` | [IRJADOS](https://yoona.pl/markiza-irjados/) |
| Markizy wolnostojące | `2024/06/markiza-wolnostojaca-1024x768.jpg` | `2024/06/markiza-olsztyn-1024x768.jpg` | [Markiza dwustronna — ANGELO, Łódź](https://yoona.pl/markiza-dwustronna-angelo-lodz/) |
| Znakowanie markiz | `2024/07/markiza-1024x768.jpg` | `2026/06/markizy-olsztyn2.jpg` | [Rybakówka](https://yoona.pl/markiza-z-logo-rybakowka/), [IRJADOS](https://yoona.pl/markiza-irjados/) |

Dokładne adresy i opisy są w `content/service-guides.json`. Opisy źródłowych
realizacji potwierdzają plexi, dibond, elementy frezowane, markizę elewacyjną,
dwustronną i znakowanie. Nie potwierdzają modelu SELT danej sfotografowanej
instalacji. Dlatego opisy zdjęć podają rzeczywistą realizację, bez przypisywania
jej modelu SELT lub DUO. Nazwy tych systemów w ofercie wynikają z informacji
właściciela o sprzedawanym asortymencie.

Dobór oparto na pochodzeniu zdjęć i opisach realizacji. Ich aktualnej dostępności
HTTP i kadrowania nie można było obejrzeć przez zablokowane połączenie. Lokalne
zrzuty z obrazami testowymi potwierdzają układ, a nie wygląd tych fotografii.
Po instalacji skontrolować po jednym widoku każdej z sześciu ofert na telefonie.

## SEO techniczne: co wynika z kodu

| Obszar | Ustalenie | Znaczenie |
|---|---|---|
| Tytuł i opis strony | Szablon wywołuje standardowe `wp_head()`; importer zapisuje do `rank_math_title` i `rank_math_description`. | Rank Math zachowuje własność metadanych. Wyłączenie samej warstwy wyglądu nie usuwa wdrożonych metadanych. |
| Canonical, robots i mapa strony | Warstwa wyglądu nie generuje drugiego canonicala, własnej mapy ani reguły indeksowania. | Oceniać wynik WordPressa i Rank Math; nie dopisywać dodatkowych tagów do szablonu. |
| Nagłówek H1 | Szablony sprawdzają H1 w zachowanej treści przed dodaniem własnego. | Zachowują semantykę oryginału; nie usuwają samodzielnie H1 dodanych później w edytorze. |
| Edytowalność | Sama warstwa prezentacji nie zmienia treści w bazie. Podmiany znanych sekcji oraz opisów realizacji mają kontrolę źródłowych hashy. Osobny, jawny zapis układu do Divi zmienia treść wybranej strony i wymaga kopii oraz podglądu. | Nowsza ręczna edycja nie jest automatycznie nadpisywana starym opisem. Tryb zapisany w Divi musi respektować późniejsze zmiany modułów. |
| Galerie i dół ofert | Renderowane jest pełne drzewo Divi, z zachowaniem sekcji, wierszy i modułów. | Zdjęcia, formularze, akordeony i dalsze treści zachowują swoich rodziców. |
| Status i hasło | Wybór opisów i kart sprawdza publikację i brak hasła. Tryby REST, RSS, embed, preview i builder są wyłączone z nowego wyglądu. | Publiczny katalog nie ma służyć ujawnianiu szkiców ani stron chronionych. |
| Paginacja realizacji | Główne zapytanie WordPressa ma 24 pozycje i natywną paginację; SQL liczy wpisy ze zdjęciem przed podziałem. | Kolejne strony nie powstają przez ręczne wycinanie tablicy wpisów. |
| Linkowanie | Linki do usług podrzędnych powstają dopiero dla opublikowanej strony. | Przed użyciem przycisku dodania sześciu ofert nie pojawiają się odnośniki do nieistniejących podstron. |
| Obrazy i fonty | Obraz na górze ma wysoki priorytet; pozostałe mogą być ładowane leniwie. Fonty są lokalne, z polskimi znakami i `font-display:swap`. | Nie potrzeba kolejnego połączenia do zewnętrznego serwera fontów. Rozmiar zdjęć i cache wymagają kontroli na produkcji. |
| Pomiar | `measurement.js` wykorzystuje istniejący tag Site Kit lub istniejący GTM; nie instaluje drugiego Analytics. | Kliknięcia kontaktu są zdarzeniami pomocniczymi, wysyłanymi przy wykrytej zgodzie. Sukces formularza wymaga osobnej weryfikacji integracji. |

W eksporcie z 15 października witryna miała `blog_public=1`, przyjazne adresy
`/%postname%/` i przypisaną stronę realizacji. Nie znaleziono `noindex` w
wyeksportowanych metadanych stron. To stan historyczny: nie dowodzi obecnego
robots.txt, nagłówka `X-Robots-Tag`, odpowiedzi serwera ani indeksacji Google.

W przeglądzie zauważono też dwa konkretne miejsca do korekty w kodzie:
nadawanie wysokiego priorytetu aż sześciu zdjęciom katalogu oraz pomijanie
odnośnika „Pełna oferta” w opisie realizacji oklejania łodzi. Wynik poprawek
i aktualne testy integracyjne są rozliczane w
[wynikach testów](WYNIKI-TESTOW.md); ta lista nie jest pomiarem szybkości serwisu.

## Lokalne SEO: następne działania zależne od produkcji

Największa wartość to kompletna oferta, autentyczne realizacje i łatwy kontakt,
zamiast kolejnych kopii tekstu z podmienioną nazwą miasta. Olsztyn i Ostróda
pozostają równorzędnymi, rzeczywistymi punktami kontaktu. Nie dodawać
niepotwierdzonych oddziałów w innych województwach ani deklaracji partnerstwa SELT.

1. W Search Console sprawdzić reprezentatywne adresy usług, realizacji i obu
   stron firmowych przez inspekcję URL: indeksowanie, wybrany canonical i treść
   widzianą przez Google. Osobno sprawdzić mapę Rank Math i ewentualne blokady
   w robots.txt oraz nagłówkach HTTP. Test lokalny nie zastępuje tych raportów.
2. Analizować zapytanie razem z adresem strony. Szczególnie porównać CNC i trzy
   oferty materiałowe, markizy i ich podstrony oraz reklamę na rusztowaniu
   i megaboardy. Podobna tematyka nie dowodzi kanibalizacji. Nie scalać ani nie
   zmieniać canonicali bez danych o intencji i adresach wyświetlanych w Google.
3. W Profilach Firmy dla rzeczywistych lokalizacji ujednolicić nazwy, telefony,
   adresy, godziny i odpowiednie kategorie. Dodać aktualne zdjęcia punktów oraz
   potwierdzone realizacje. Dane adresowe w schema muszą odpowiadać faktycznym
   punktom; nie tworzyć drugiego sprzecznego schematu LocalBusiness.
4. Nowe realizacje opisywać krótko: rzeczywisty zakres, materiał, miejscowość,
   zdjęcia i jedna powiązana usługa. Nie dopisywać ceny, efektu sprzedażowego,
   parametrów ani opinii klienta, których dokumentacja nie potwierdza.
5. Porównywać pełne okresy Search Console z rozdzieleniem marki, usług, urządzeń
   i miast. Jakość leadów oceniać w rejestrze zapytań; kliknięcie telefonu lub
   e-maila samo w sobie nie potwierdza rozmowy ani zlecenia.

Szczegółowy plan pozostaje w [planie lokalnego SEO](PLAN-SEO-LOKALNE.md),
a konfiguracja istniejącego pomiaru w [instrukcji Site Kit i GA4](GOOGLE-POMIAR-I-ADS.md).
Nie deklarujemy pozycji TOP 1 na podstawie zmian strony ani testów lokalnych.

## Przegląd obecnych wtyczek

Wersje poniżej pochodzą z przekazanej listy, a nie z odczytu aktualnego panelu
WordPressa. Nie są potwierdzeniem najnowszej dostępnej wersji lub braku podatności.

| Wtyczka | Przekazana wersja | Zalecana rola / ustawienie |
|---|---|---|
| Rank Math SEO | 1.0.280 | Jeden główny dostawca tytułów, opisów, canonicali, schema i mapy strony. |
| Rank Math SEO PRO | 3.0.124 | Rozszerzenie wersji darmowej. Obecność obu jest prawidłowa; PRO wymaga podstawowej wtyczki. Nie usuwać podstawy w ramach „odchudzania”. |
| Google Site Kit | 1.189.0 | Istniejące połączenie Analytics i Search Console. Wybrać jedną integrację, która wstawia kod Analytics; nie dublować jej modułem Analytics Rank Math lub kodem motywu. |
| CookieYes | 3.5.7 | Jeden panel zgód. Potwierdzić działanie odmowy, zgody i zmiany decyzji razem z opóźnianiem JavaScript. Nie narzucać zgody z własnego kodu. |
| LiteSpeed Cache | 7.9.1 | Cache i dostarczanie zasobów; funkcja cache serwera zależy od hostingu. Testować zmiany CSS/JS z formularzem, zgodami, Divi i Site Kit. |
| Imagify | 2.3.4 | Istniejąca optymalizacja zdjęć. Wybrać jeden mechanizm konwersji/kompresji i dostarczania WebP/AVIF: Imagify albo odpowiednie funkcje LiteSpeed. Nie uruchamiać podwójnego przepisywania tych samych obrazów. |
| Limit Login Attempts Reloaded | 3.3.10 | Ochrona logowania; niezależna od redakcji SEO. Nie wymaga zastąpienia dla tej zmiany. |
| Trustindex | 14.2 | Autentyczne opinie z już podłączonego źródła. Sprawdzić widżet i ewentualne pobieranie zasobów; nie dopisywać własnych ocen ani zbędnego schematu gwiazdek. |
| Yoona — wygląd i SEO | pakiet tego repozytorium | Prezentacja i kontrolowany zapis istniejących treści. Nie zastępuje ustawień usług Google ani licencji zewnętrznych wtyczek. |

Do wykonania tego zakresu nie jest potrzebna kolejna wtyczka SEO, Analytics,
formularzy, cache ani fontów, więc nie przygotowano automatycznego instalatora
dublujących rozszerzeń. Nie instalowano ani nie usuwano zdalnie wtyczek.
Ustawienia kont, klucze API, licencje i istniejący formularz pozostają w obecnych
integracjach. Zgodność konfiguracji Imagify/LiteSpeed oraz jednego tagu Analytics
trzeba potwierdzić w panelu i w sesji gościa.

Po instalacji paczki odświeżyć wygląd i cache LiteSpeed, następnie sprawdzić
kontakt, galerię i zgody jako wylogowany użytkownik. Jeżeli sześć ofert już
istnieje, nie trzeba dodawać ich ponownie, aby zmieniły się fotografie warstwy
wyglądu. Samo przesłanie ZIP nie potwierdza ponownego wdrożenia starszej paczki SEO.
