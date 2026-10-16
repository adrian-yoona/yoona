# Przebudowa wyglądu Yoona w wersji 3

Wersja 3 realizuje brakujący zakres wizualny, wskazany przez właściciela w załączonym obrazie. Poprzednia wersja zmieniała treści i metadane, bez przebudowy szaty graficznej.

## Układ według wzoru

- Jasny nagłówek z marką YOONA, nawigacją i pomarańczowym przejściem do wyceny.
- Ciemny hero ze zdjęciem witryny, dużym hasłem „Robimy marki widoczne” i dwoma przejściami.
- Jasna sekcja ośmiu zdjęciowych kart usług: banery, beczki, neony, litery 3D, oklejanie, markizy, windery i reklama dla gastronomii.
- Ciemny pas procesu: od rozmowy i projektu przez produkcję do montażu.
- Siatka istniejących realizacji, pomarańczowe wezwanie do kontaktu, jasny blok o firmie i ciemne zamknięcie z kontaktem i stopką.
- Wspólna oprawa nawigacji, typografii i stopki na podstronach. Układ dostosowuje się do telefonu; menu nie wymaga JavaScript.

Kolory: pomarańcz i czerń z ciepłym jasnym tłem. Nagłówki używają lokalnego Archivo Black, z polskimi znakami i dołączoną licencją SIL OFL. Strona nie pobiera fontów z Google.

Nie odwzorowano panelu administracyjnego, paska wskaźników SEO ani przypadkowych pasków przeglądarki widocznych na zdjęciu referencyjnym. Nie dodano niepotwierdzonych liczb „25+” lub „1000+”. Dane kontaktowe, zdjęcia i odnośniki pochodzą z eksportu istniejącej witryny. Nie dopisano nowego formularza, który udawałby działającą wysyłkę; wycena prowadzi do dotychczasowego kontaktu.

## Mechanizm i cofanie

Wygląd jest domyślnie włączoną warstwą szablonów wtyczki. Nie zapisuje nowego układu do `post_content`, nie usuwa modułów Divi i nie zmienia adresów. Strona główna ma własną kompozycję, a standardowa treść podstron jest przetwarzana przez WordPress. Zmiany w dotychczasowym układzie Divi strony głównej będą widoczne po wyłączeniu nowego szablonu; nie edytują nowej kompozycji.

Panel **Narzędzia → Yoona SEO** pokazuje stan wyglądu, przełącznik powrotu i odświeżenie cache. Wyłączenie wyglądu lub dezaktywacja wtyczki przywraca szablony motywu. Wcześniej zapisane teksty i metadane Rank Math pozostają i mają osobne kopie w historii.

Włączenie wyglądu nie uruchamia zapisu pakietu 200 pozycji. Panel osobno podaje liczbę wartości zgodnych z pakietem, gotowych do zapisu i zablokowanych. Nie wolno uznawać samej instalacji za potwierdzenie zapisu SEO.

## Sprawdzenie i ograniczenia

Wyniki rzeczywiście wykonanych testów podaje [dokument testów](WYNIKI-TESTOW.md). Warstwa zachowuje `wp_head`, `wp_body_open` i `wp_footer`, dzięki którym działają standardowe zasoby i tagi Rank Math. Nie dodaje drugiego generatora SEO.

Adresy zdjęć wybrano z istniejącej treści witryny. Filtr sieciowy uniemożliwił pobranie ich w środowisku, dlatego nie potwierdzono aktualnych plików ani identycznego kadru ze zdjęciem referencyjnym. Test układu z zastępczymi obrazami sprawdza geometrię, nie fotografię produkcyjną.

Licencjonowany motyw Divi i dodatki do niego nie są dołączone do testów. Po aktualizacji właściciel powinien sprawdzić stronę główną, typową usługę, realizację i kontakt na komputerze oraz telefonie, zwłaszcza galerie, formularze i przyciski. Cache hostingu lub CDN może wymagać czyszczenia w swoim panelu. Nie wykonano zdalnego wdrożenia na yoona.pl ani pomiaru szybkości produkcji.
