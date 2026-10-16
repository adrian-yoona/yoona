=== Yoona SEO — poprawki Divi i Rank Math ===
Contributors: yoona
Tags: seo, content, divi
Requires at least: 6.0
Requires PHP: 7.4
Stable tag: 2.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Gotowe poprawki tekstów i metadanych yoona.pl, podgląd, zapis partiami oraz kopie z możliwością cofnięcia.

== Description ==

Pakiet opracowano na podstawie eksportu właściciela yoona.pl z 15 października 2026 r. Obejmuje metadane 200 elementów oraz 896 zmian tekstów w 56 stronach i wpisach.

Tytuły i opisy są zapisywane w natywnych polach Rank Math. Zmiany pozostają w Rank Math również po wyłączeniu Yoona SEO. Poprawki tekstów obejmują wnętrza istniejących modułów Divi et_pb_text i odpowiedzi et_pb_toggle; shortcode, atrybuty, galerie, obrazy, przyciski oraz formularze są zachowane.

Aktywacja i wybór paczki nie zmieniają witryny. Administrator sprawdza podgląd i wybiera strony. Przycisk wdrożenia wykonuje zapis partiami po 10 elementów i tworzy osobną kopię każdej partii. Historia pokazuje wszystkie partie. Hash całej treści i modułów chroni nowsze edycje; cofanie sprawdza konflikty.

Wtyczka nie zmienia adresów, robots, canonical, danych produktów ani mediów. Nie wysyła treści do zewnętrznych usług i nie wymaga klucza API. Dostępne jest również pobranie publicznych treści i wybranych ustawień do kolejnego audytu. Eksportu nie publikuj.

Audyt oparto na eksporcie. Nie potwierdza pozycji w Google, indeksacji, szybkości ani wyglądu licencjonowanego motywu Divi na produkcji. Nie gwarantuje rezultatów SEO.

== Installation ==

1. Wykonaj pełną kopię witryny w hostingu.
2. Wgraj yoona-seo-v2.zip w Wtyczki → Dodaj nową → Wyślij wtyczkę na serwer.
3. Zastąp poprzednią wersję Yoona SEO, jeśli jest zainstalowana, i pozostaw Rank Math aktywny.
4. Otwórz Narzędzia → Yoona SEO i sprawdź pakiet. W razie potrzeby wybierz Użyj pakietu dołączonego do wtyczki.
5. Sprawdź podgląd, wybierz Zaznacz wszystkie gotowe i Wgraj zmiany na zaznaczone strony. Pozostaw kartę otwartą do zakończenia.
6. Wyczyść cache i sprawdź zmienione strony na komputerze i telefonie.

== Frequently Asked Questions ==

= Czy sama instalacja poprawia stronę? =
Nie. Dopiero przycisk wgrania zmian zapisuje wybrane poprawki.

= Czy mogę cofnąć zmiany? =
Tak, w historii partii. Późniejsze zmiany objętych pól mogą spowodować konflikt, który trzeba sprawdzić. Kopie wtyczki nie zastępują pełnego backupu hostingu.

= Dlaczego strona jest zablokowana do aktualizacji? =
Jeśli treść zmieniła się po eksporcie, kontrola hashy chroni nowszą pracę. Potrzebny jest nowy eksport i zaktualizowany pakiet. Wdrożenie wymaga aktywnego Rank Math i uprawnień administratora do edycji strony.

= Czy po wyłączeniu wtyczki poprawki znikną? =
Zmiany wersji 2 pozostają w treściach oraz natywnych polach Rank Math. Aby skorzystać z historii cofania, zachowaj Yoona SEO. Starsze paczki wersji 1 mogły używać filtrów własnych metadanych i wymagać aktywnej wtyczki do ich wyświetlania.

== Changelog ==

= 2.0.0 =
* Gotowy pakiet 200 metadanych i 896 zmian tekstów z eksportu właściciela.
* Bezpośredni zapis Rank Math, kontrolowane poprawki wnętrz modułów Divi i FAQ.
* Wdrożenie partiami, podgląd, kopie każdej partii, cofanie i kontrola konfliktów.

= 1.0.0 =
* Eksport diagnostyczny i podgląd pakietu.
