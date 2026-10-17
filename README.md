# Yoona — wygląd i SEO 3.3

Wersja 3 dodaje przebudowę wyglądu według załączonego wzoru: czerń, pomarańcz, jasne tło, duża typografia, zdjęciowe karty usług, proces, realizacje i kontakt. Nowy układ strony głównej oraz wspólna oprawa podstron włączają się po aktualizacji wtyczki, o ile wcześniej nie wyłączono ich w panelu.

**[Pobierz wtyczkę yoona-seo-v3.3.zip](https://github.com/adrian-yoona/yoona/raw/refs/heads/yoona-seo-download-20261015/downloads/yoona-seo-v3.3.zip)**. To archiwum instalacyjne wtyczki, a nie ZIP całego repozytorium.

## Oferta i realizacje — wersja 3.3

- **Pełna Oferta**: zdjęciowe karty wszystkich 41 dostępnych podstron usługowych, w stylu strony głównej. Pierwsze osiem kart korzysta z tych samych zdjęć co strona główna. Strony niepubliczne i zabezpieczone hasłem są pomijane.
- **Nasze realizacje**: po 30 prac na stronę, trzy kolumny na komputerze, samo zdjęcie i tytuł. Kolejne realizacje są dostępne przez standardową nawigację WordPressa. Zdjęcie wyróżniające ma pierwszeństwo przed zdjęciem z treści; wpisy bez zdjęcia są pomijane.
- Szerokie siatki z równymi marginesami, od 20 px na telefonie do 72 px na dużym ekranie, bez dawnego ograniczenia szerokości do 1180 px.
- Stary blok **Masz pytanie? Kontakt** ze zdjęciem pokrętła jest pomijany na 44 rozpoznanych podstronach. Aktualne dane kontaktowe i nowa stopka pozostają dostępne.

Te zmiany działają po aktualizacji wtyczki i odświeżeniu wyglądu/cache. Nie wymagają ponownego wgrywania SEO i nie kasują treści w bazie. Wyłączenie nowego wyglądu przywraca dotychczasowy układ. Rozpoznanie starego bloku obejmuje oryginał oraz wariant po dołączonych poprawkach tekstów.

## Nowa góra podstron ofertowych — wersja 3.2

Na **41 podstronach usługowych** osobny czarny blok tytułu i stary baner zastępuje jeden nagłówek. Po lewej są nazwa usługi wielkimi literami, krótki opis i przycisk wyceny, po prawej duże zdjęcie z istniejącej witryny. Na telefonie fotografia znajduje się pod tekstem. Dodano przejście do zakresu oferty oraz nawigację do listy usług.

Zmiana działa po aktualizacji i odświeżeniu wyglądu, bez ponownego zapisu SEO. Obejmuje rozpoznane banery z eksportu, zarówno przed, jak i po zastosowaniu dołączonej paczki tekstów. Jeśli stary baner był później edytowany, wtyczka zachowuje dotychczasowy widok. Nie przepisuje treści w bazie; dalsza oferta, galerie i formularze przechodzą przez standardowy rendering Divi.

## Naprawa zapisu SEO — wersja 3.1.1

Usunięto błąd, przez który kliknięcie wgrania zmian wysyłało żądanie pod błędny adres i kończyło się komunikatem „Serwer przerwał odpowiedź” przy pustej historii. Pole formularza `action` zasłaniało właściwość o tej samej nazwie. Skrypt pobiera teraz właściwy adres z atrybutu formularza. Przy błędzie odpowiedzi pokazuje kod HTTP lub informację o przekierowaniu do logowania i zatrzymuje zapis bez automatycznego ponawiania.

Naprawa jest również w wersji 3.3. Po zastąpieniu wtyczki odśwież panel przed uruchomieniem zapisu. Partie po 10 pozycji, kopie i kontrola konfliktów pozostają w użyciu.

## Dopracowanie wersji 3.1

Poprawiono warstwy zdjęcia w nagłówku: przyciemnienie znajduje się teraz nad fotografią, pod tekstem. Kadr ma spokojniejszą kolorystykę, a mocniejszy gradient po lewej ułatwia czytanie. Opis ma 18 px na komputerze i 16 px na telefonie. Nowy lokalny Manrope obsługuje opisy i interfejs, a Archivo Black pozostaje fontem dużych nagłówków z luźniejszym odstępem liter.

Nazwy usług w kartach, pasek PROJEKT / DRUK / PRODUKCJA / MONTAŻ, oferta w stopce i nagłówki na stronie Pełna Oferta są wyświetlane wielkimi literami. Zachowano naturalną pisownię akapitów, a nazwy w bazie nie są przepisywane.

## Aktualizacja wyglądu

1. Wykonaj pełną kopię witryny w hostingu.
2. W WordPressie wybierz **Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer**. Wgraj `yoona-seo-v3.3.zip`, zastąp poprzednią Yoona SEO i upewnij się, że jest aktywna. Rank Math pozostaw aktywny.
3. Odśwież **Narzędzia → Yoona SEO**. U góry powinna być **wersja 3.3.0** i stan nowego wyglądu. Jeśli wygląd jest wyłączony, włącz go w tym panelu.
4. Otwórz stronę po aktualizacji. Jeśli nadal widzisz poprzedni układ, użyj odświeżenia wyglądu/cache w panelu. Cache hostingu lub CDN może wymagać wyczyszczenia w jego własnym panelu.
5. Sprawdź Pełną Ofertę, listę realizacji wraz z kolejną stroną oraz typową usługę i kontakt na komputerze oraz telefonie.

**Zmiana wyglądu działa osobno od zapisu SEO.** W wersjach 1–2 nie było nowej szaty graficznej. Sam zapis tytułów i opisów Rank Math nie przebudowywał układu. Panel wersji 3 pokazuje osobno wygląd oraz liczbę pozycji zgodnych z paczką, gotowych do zapisu i zablokowanych.

## Teksty i Rank Math

Dołączony pakiet treści pozostaje oparty na eksporcie WordPressa yoona.pl z 15 października 2026 r.: **47 stron i 153 wpisy**. Obejmuje nowe tytuły i opisy Rank Math dla 200 pozycji oraz **896 zmian modułów w 56 elementach**: 45 stronach i 11 realizacjach. To 716 modułów tekstowych i 180 odpowiedzi FAQ Divi. Nie przeprowadzono pełnej redakcji treści pozostałych 142 realizacji.

Jeśli te poprawki nie są jeszcze zapisane: w **Narzędzia → Yoona SEO** wybierz **Użyj pakietu dołączonego do wtyczki**, sprawdź podgląd, kliknij **Zaznacz wszystkie gotowe → Wgraj zmiany na zaznaczone strony**. Pozostaw kartę otwartą do końca. Zapis odbywa się po 10 pozycji, z kopią każdej partii. Nie uruchamiaj ponownie zapisu pozycji już zgodnych z pakietem.

Tytuły i opisy zapisują się w natywnych polach Rank Math. Aktualizacja tekstów obejmuje wybrane wnętrza modułów Divi i zachowuje pozostałe bajty źródła. Polityka prywatności i strona listy realizacji otrzymują tylko metadane. Hash całej treści i modułów chroni edycje wykonane po eksporcie; zablokowane strony wymagają nowego eksportu zamiast obchodzenia kontroli.

## Jak działa i jak wrócić

Nowy wygląd jest warstwą szablonów wtyczki, a nie nadpisaniem układu Divi w bazie. Strona główna korzysta z przygotowanego układu; podstrony zachowują własną treść przez standardowy mechanizm WordPressa. Stare moduły, galerie i formularze pozostają w bazie. Zdjęcia pochodzą z istniejących adresów witryny, a nagłówki korzystają z lokalnego Archivo Black, a teksty i interfejs z Manrope; obie rodziny mają dołączone licencje OFL.

Wyłączenie nowego wyglądu w panelu przywraca szablony motywu. Dezaktywacja Yoona SEO również usuwa tę warstwę wizualną. Zapisane wcześniej treści oraz natywne metadane Rank Math pozostają; cofa się je osobno przez historię partii. Kopie wtyczki nie zastępują pełnego backupu hostingu.

Nowa strona główna nie jest edytowana w dotychczasowym układzie Divi. Jej strukturę i teksty zawierają szablony wtyczki; dotychczasowa zawartość Divi pozostaje dostępna po wyłączeniu nowego wyglądu. Warstwa nie zmienia adresów URL, przekierowań, robots/canonical ani globalnych ustawień Rank Math.

## Audyt, zakres i testy

- [Opis przebudowy wyglądu](docs/ZMIANY-WYGLADU.md).
- [Audyt SEO 200 pozycji](audit/AUDYT-SEO-YOONA.md) i [zakres poprawek treści](audit/zakres-poprawek.json).
- [Teksty stron A](copy/STRONY-A.md), [teksty stron B](copy/STRONY-B.md), [metadane i poprawki realizacji](copy/WPISY-SEO.md).
- [Wyniki testów](docs/WYNIKI-TESTOW.md) i [odtworzenie testów](tests/README.md).

Audyt obejmuje otrzymany eksport. Środowisko nie mogło odczytać produkcyjnej witryny ani jej zdjęć, więc nie zmierzono działającego HTML, Core Web Vitals, indeksacji ani ruchu. Źródłowe adresy zdjęć pochodzą z eksportu; ich aktualnej dostępności i kadru nie potwierdzono. Licencjonowany Divi nie jest zainstalowany w środowisku testowym; jego rzeczywisty wygląd, galerie i formularze trzeba sprawdzić po aktualizacji na witrynie.

Nie dodano niepotwierdzonych liczników lat działalności lub realizacji, cen, certyfikatów ani gwarancji wyników.

## Rozwój

Wymagania: WordPress 6.0+, PHP 7.4+, administrator oraz uprawnienia do edycji stron przy zapisie treści. Wersje faktycznie przetestowane podaje dokument testów.

```sh
python3 -m unittest discover -s tests -p 'test_audit*.py' -v
bash tests/record-wordpress.sh
python3 tools/package_plugin.py
```

Budowanie pakietu treści wymaga prywatnego eksportu właściciela:

```sh
python3 tools/build_changes.py --source /sciezka/do/prywatnego-eksportu.json
```

Surowego eksportu i danych logowania nie publikuj. Wtyczka nie potrzebuje tokena ani hasła do działania na zainstalowanym WordPressie. Publikacja ZIP-a na GitHubie nie jest zdalnym wdrożeniem na yoona.pl; właściciel aktualizuje wtyczkę na swojej witrynie.
