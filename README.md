# Yoona SEO — gotowy pakiet poprawek

Pakiet przygotowano na podstawie eksportu WordPressa yoona.pl z 15 października 2026 r.: **47 stron i 153 wpisy**. Zawiera nowe tytuły i opisy Rank Math dla wszystkich 200 pozycji oraz **896 zmian tekstów w 56 elementach**: 45 stronach i 11 realizacjach. Zmiany obejmują 716 modułów tekstowych Divi i 180 odpowiedzi FAQ.

## Pobieranie i instalacja

Gotowa wtyczka: [downloads/yoona-seo-v2.zip](downloads/yoona-seo-v2.zip). Otwórz plik na GitHubie i wybierz **Download raw file**. Nie instaluj archiwum całego repozytorium.

1. Wykonaj pełną kopię witryny w hostingu.
2. W WordPressie wybierz **Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer**. Wgraj `yoona-seo-v2.zip` i zastąp poprzednią wersję Yoona SEO przesłaną wersją 2.0.0. Rank Math pozostaw aktywny.
3. Otwórz **Narzędzia → Yoona SEO**. Sprawdź, czy widzisz gotowy pakiet dla 200 stron i wpisów. Jeśli wcześniej importowano inną paczkę, wybierz **Użyj pakietu dołączonego do wtyczki**.
4. Sprawdź podgląd, kliknij **Zaznacz wszystkie gotowe**, a następnie **Wgraj zmiany na zaznaczone strony**. Pozostaw kartę otwartą do zakończenia postępu. Zapis odbywa się po 10 pozycji, z kopią każdej partii.
5. Wyczyść cache witryny w używanej wtyczce, Divi, hostingu lub CDN i otwórz zmienione strony na komputerze oraz telefonie.

**Aktualizacja i aktywacja wtyczki same nie zapisują poprawek. Konieczny jest przycisk wgrania zmian w jej panelu.** Nie wykonano zdalnego wdrożenia na yoona.pl.

## Co zmienia ten pakiet

- Tytuły i opisy zapisuje bezpośrednio w polach `rank_math_title` i `rank_math_description`. Po zapisaniu pozostają w Rank Math także po wyłączeniu Yoona SEO.
- Teksty aktualizuje wyłącznie we wnętrzach wybranych modułów `et_pb_text` i odpowiedzi `et_pb_toggle`. Zachowuje pozostałe bajty źródła Divi, w tym shortcode, ich atrybuty, galerie, obrazy, przyciski i kod formularzy.
- Usuwa mylne nagłówki i nazwy usług/klientów, pozostałości instrukcji redakcyjnych, sztuczne frazy i nieuzasadnione zapewnienia. Uporządkowuje H1 w poprawianych stronach na poziomie źródła.
- Polityka prywatności i strona listy realizacji mają zmieniane wyłącznie metadane. W pozostałych 142 realizacjach aktualizowane są metadane, a ich treści pozostają bez zmian.
- Nie zmienia adresów URL, przekierowań, ustawień robots/canonical, danych mediów ani globalnych szablonów Divi.

Treści wykonano na podstawie otrzymanego eksportu. Nie dopisano niepotwierdzonych cen, certyfikatów, statystyk skuteczności ani nowych usług.

## Zabezpieczenia i cofanie

Przed każdą partią powstaje trwała kopia zmienianych pól i metadanych. W sekcji **Cofnij zmiany** dostępne są wszystkie partie. Cofanie sprawdza aktualny stan i zgłasza konflikt, jeśli od wdrożenia ktoś zmienił objęte nim pola. Standardowy edytor WordPressa nie współdzieli blokady importera, dlatego zastosowano również porównanie stanu przed zapisem i odczyt po zapisie.

Aktualizacja tekstów wymaga zgodnego SHA-256 całej treści źródłowej i zmienianych modułów. Jeśli strona została edytowana po eksporcie z 15 października, panel zablokuje jej zmiany — wtedy potrzebny jest nowy eksport i aktualizacja pakietu. Nie obchodź tej kontroli przez usunięcie hashy.

Kopia wtyczki obejmuje jej zmiany i nie zastępuje pełnego backupu hostingu. Zachowaj wtyczkę, jeśli chcesz korzystać z historii cofania.

## Audyt i teksty do przeglądu

- [Audyt wszystkich 200 elementów](audit/AUDYT-SEO-YOONA.md).
- [Zakres poprawek w JSON](audit/zakres-poprawek.json).
- [Teksty stron — część A](copy/STRONY-A.md), [część B](copy/STRONY-B.md).
- [Metadane realizacji i poprawki 11 wpisów](copy/WPISY-SEO.md).
- [Wyniki testów](docs/WYNIKI-TESTOW.md) i [instrukcja testowania](tests/README.md).

Audyt obejmuje dane oraz treści z eksportu. Dostęp sieciowy do yoona.pl pozostawał blokowany przez środowisko, więc nie zmierzono działającej witryny, Core Web Vitals, indeksacji Google ani ruchu. Brak własnego pola Rank Math w bazie nie dowodzi braku tagu w wynikowym HTML. Nie uruchamiano licencjonowanego motywu Divi w środowisku testowym; sprawdzano zachowanie kodu źródłowego i mechanizm shortcode WordPressa, a nie wygląd produkcyjnego motywu.

## Odtworzenie i rozwój

Wymagania wtyczki: WordPress 6.0+, PHP 7.4+, administrator oraz uprawnienia do edycji objętych stron. Testowane wersje i wyniki podaje dokument testów.

```sh
python3 -m unittest discover -s tests -p 'test_audit*.py' -v
bash tests/record-wordpress.sh
```

Budowanie pakietu z prywatnego eksportu właściciela:

```sh
python3 tools/build_changes.py --source /sciezka/do/prywatnego-eksportu.json
```

Surowego eksportu nie publikuj w repozytorium. Opublikowane pakiety zawierają nowe treści i hashe źródła, a nie kopię pełnego eksportu. Narzędzie nie przesyła danych do zewnętrznych usług.
