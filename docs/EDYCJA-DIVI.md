# Edycja nowego układu w Divi — 3.5.0

Domyślny wygląd YOONA powstaje z szablonów PHP i publicznych danych wtyczki.
Sam przycisk Visual Builder pokazuje wtedy stare, zapisane źródło, a nie
wszystkie elementy nowego wyglądu. Dlatego dodano jawny zapis pojedynczej
strony do rzeczywistych modułów Divi. Nie ma automatycznej migracji stron.

1. W WordPressie otwórz **Strony → wybrana strona → Edytuj**.
2. W panelu **YOONA — edycja układu w Divi** wybierz **Edytuj układ w Divi**.
3. Sprawdź podgląd źródła, a następnie wybierz **Zapisz układ i edytuj w Divi**.
4. Otwórz **Visual Builder** lub standardową edycję strony i użyj Divi.

Teksty, zdjęcia i przyciski nowego układu są osobnymi modułami. Sekcje,
wiersze, kolumny, akordeony i slajdy można zmieniać i kopiować standardowymi
narzędziami Divi. Slider strony głównej używa natywnego modułu Divi.
Nie instalujemy zamiennika edytora Divi.

Zapis dotyczy wyłącznie wskazanej strony. Po nim zapisane `post_content`
jest źródłem wyglądu tej strony; szablony YOONA przestają ponownie dodawać
nagłówki i poradniki. Aktualizacja wtyczki nie zastąpi ręcznych zmian.
Nagłówek i stopka witryny nadal pochodzą z wtyczki; zmiany w nich nie są
modułami treści pojedynczej strony. Nie zapisujemy przy tej operacji danych
Rank Math, ustawień Site Kit ani zdarzeń pomiarowych.

W realizacjach lista jest dynamiczna. W natywnym module **Kod** znajduje się
`[yoona_realizacje]`: 24 opublikowane realizacje ze zdjęciami, tylko zdjęcie
i tytuł oraz paginacja. Cały moduł, jego kolumnę, wiersz i sekcję można
przenosić i kopiować w Divi. Treść i fotografie konkretnej realizacji edytuje
się w jej własnym wpisie, zamiast ręcznie przebudowywać statyczną siatkę.
Zdjęcia z treści wpisów działają także bez osobnego obrazu wyróżniającego.
Lista korzysta z tych samych warunków zdjęć i zliczania co zwykły indeks.

Oryginalne galerie, formularze i pozostałe moduły istniejących ofert oraz
realizacji zachowują całe swoje drzewa Divi. W znanych ofertach zastępujemy
tylko nagłówek i wstęp zgodny z zatwierdzonym odciskiem źródła. Wstęp
zmieniony ręcznie jest zachowywany. Na stronie głównej i w katalogu pełnej
oferty zachowujemy całe oryginalne sekcje zawierające formularze, mapy,
galerie, kod i dodatkowe shortcode. Nie przenosimy samych formularzy czy
zdjęć poza ich rodziców. Dodatkowe osadzenie poza sekcją Divi zatrzymuje
konwersję z komunikatem, zamiast znikać z treści.

Przed zapisem powstaje niezależna kopia treści i dokładnych wartości
metadanych Divi. Dostępne jest **Przywróć poprzedni układ** z podglądem
obu źródeł. Przed przywróceniem zapisywana i weryfikowana jest również
kopia aktualnego układu z ręcznymi zmianami. Można ją odzyskać przyciskiem
**Odzyskaj układ sprzed przywracania**, także gdy rewizje WordPressa są
wyłączone. Kopie pozostają chronionymi metadanymi tego wpisu. Nie wysyłamy
źródeł stron ani kopii do GitHuba.

Wymagane są aktywne Divi/Divi Builder oraz uprawnienia administratora do
konwersji i przywracania. Zwykłe późniejsze zmiany w Divi korzystają z
normalnych uprawnień WordPressa do edycji wpisu. Formularze zapisujące
wymagają POST i nonce. Zmienione źródło lub ustawienia od otwarcia podglądu,
aktywna edycja przez inną osobę, brak zgodnej kopii albo ograniczenie HTML
konta zatrzymuje operację. Każda strona ma osobną blokadę z unikalnym kluczem
w bazie. Nie ma równoległych zapisów tej samej strony przez to narzędzie.
Po błędzie zapisu lub przywracania kopie są zachowywane; sukces przywrócenia
jest zgłaszany dopiero po sprawdzeniu całej treści i metadanych.

Testy używają prawdziwego WordPressa i bazy oraz syntetycznych callbacków
Divi. Potwierdzają strukturę natywnych shortcode, zachowanie źródła,
kopiowanie modułów, blokady i kopie. Licencjonowany Divi nie jest dołączony
do repozytorium, dlatego testy nie potwierdzają działania interfejsu
Visual Builder konkretnej wersji Divi ani wysyłki formularzy na produkcji.
