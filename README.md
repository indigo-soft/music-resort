# MP3 Resort Tool

Консольна команда для автоматичного сортування MP3 файлів за виконавцями.

## Встановлення

1. Встановіть залежності:

```bash
composer install
```

1. Зробіть консольний файл виконуваним (Linux/Mac):

```bash
chmod +x bin/console
```

## Використання

### Основна команда

```bash
php bin/console mp3:resort <source_directory> <destination_directory>
```

### Режим симуляції (dry-run)

```bash
php bin/console mp3:resort <source_directory> <destination_directory> --dry-run
```

### Приклади

**Windows:**

```bash
php bin/console mp3:resort "C:\Music\Unsorted" "C:\Music\Sorted"
```

**Linux/Mac:**

```bash
php bin/console mp3:resort "/home/user/music/unsorted" "/home/user/music/sorted"
```

**Режим симуляції (Windows):**

```bash
php bin/console mp3:resort "C:\Music\Unsorted" "C:\Music\Sorted" --dry-run
```

**Режим симуляції (Linux/Mac):**

```bash
php bin/console mp3:resort "/home/user/music/unsorted" "/home/user/music/sorted" --dry-run
```

## Функціональність

### Що робить команда:

1. **Сканує** вихідну теку на наявність MP3 файлів
2. **Читає метадані** кожного файлу
3. **Витягує інформацію** про виконавця з тегів
4. **Обробляє множинних виконавців** — вибирає першого
5. **Створює теки** за іменами виконавців
6. **Переміщує файли** до відповідних тек
7. **Обробляє помилки** — пропускає пошкоджені файли

### Режим симуляції (--dry-run):

- **Не змінює файлову систему** — жодні файли не переміщуються
- **Не створює теки** — лише симулює їх створення
- **Показує всі повідомлення** — як при звичайному виконанні
- **Відображає план дій** — що буде зроблено з кожним файлом
- **Безпечний тест** — можна перевірити результат без ризику

### Обробка виконавців:

- Пошук в тегах: `artist`, `albumartist`, `band`, `performer`
- Розділення множинних виконавців: `;`, `,`, `/`, `&`, `feat.`, `ft.`, `featuring`
- Санітизація імен тек (видалення недопустимих символів)
- Обмеження довжини імені теки (100 символів)

### Обробка помилок:

- Файли без метаданих - пропускаються
- Пошкоджені MP3 файли — пропускаються
- Файли без інформації про виконавця — пропускаються
- Конфлікти імен файлів — автоматичне перейменування

## Структура проєкт

```
├── bin/
│   └── console              # Точка входу консольної програми
├── src/
│   └── Command/
│       └── ResortMp3Command.php  # Основна логіка команди
├── composer.json            # Залежності проекту
└── README.md               # Документація
```

## Залежності

- **PHP 8.4+** - мінімальна версія PHP
- **wapmorgan/mp3info** — читання MP3 метаданих
- **symfony/console** — консольний інтерфейс
- **symfony/finder** — пошук файлів
- **symfony/filesystem** — операції з файловою системою

## Приклад виводу

### Звичайний режим:

```
MP3 File Resorting
==================

 ! [NOTE] Created artist folder: The Beatles

 ! [NOTE] Created artist folder: Queen

 ! [WARNING] Skipped file corrupted.mp3: No artist information found in metadata

 3/3 [============================] 100%

 [OK] MP3 resorting completed!
 [OK] Processed files: 2
 [OK] Skipped files (errors): 1
```

### Режим симуляції (--dry-run):

```
 ! [NOTE] DRY-RUN MODE: No filesystem changes will be made

MP3 File Resorting
==================

 ! [NOTE] Created artist folder: The Beatles
 ! [NOTE] Would move file: song1.mp3 -> The_Beatles/song1.mp3

 ! [NOTE] Created artist folder: Queen
 ! [NOTE] Would move file: song2.mp3 -> Queen/song2.mp3

 ! [WARNING] Skipped file corrupted.mp3: No artist information found in metadata

 3/3 [============================] 100%

 [OK] MP3 resorting completed!
 [OK] Processed files: 2
 [OK] Skipped files (errors): 1
```

## Додаткові можливості

### Перегляд довідки:

```bash
php bin/console mp3:resort --help
```

### Режим симуляції:

```bash
php bin/console mp3:resort <source> <destination> --dry-run
```

### Перегляд версії:

```bash
php bin/console --version
```

### Список доступних команд:

```bash
php bin/console list
```

## Тестування

Проєкт уключає повний набір тестів для забезпечення якості коду, написаних з використанням фреймворку Pest.

### Структура тестів:

```
tests/
├── Unit/                     # Модульні тести
│   └── ResortMp3CommandTest.php
├── Integration/              # Інтеграційні тести
│   └── ResortMp3CommandIntegrationTest.php
└── Fixtures/                 # Допоміжні класи для тестів
    └── Mp3TestHelper.php
```

### Запуск тестів:

1. **Встановіть залежності для розробки:**

```bash
composer install
```

1. **Запустіть всі тести:**

```bash
php vendor/bin/pest
```

1. **Запустіть тільки модульні тести:**

```bash
php vendor/bin/pest tests/Unit
```

1. **Запустіть тільки інтеграційні тести:**

```bash
php vendor/bin/pest tests/Integration
```

1. **Запустіть тести з детальним виводом:**

```bash
php vendor/bin/pest --verbose
```

### Покриття тестами:

**Модульні тести:**

- `sanitizeFolderName()` - санітизація імен тек
- `extractArtist()` - витягування інформації про виконавця
- Обробка спеціальних символів
- Обробка множинних виконавців
- Обробка довгих імен

**Інтеграційні тести:**

- Повний цикл виконання команди
- Обробка помилок (теки, що не існує)
- Створення тек призначення
- Обробка порожніх тек
- Обробка невалідних MP3 файлів
- Конфігурація команди

**Тестові дані:**

- Генерація валідних MP3 файлів з метаданими
- Тестування граничних випадків
- Обробка Unicode символів
- Файли з різними форматами тегів

### Конфігурація тестів:

Тести налаштовані через `tests/Pest.php`:

- Автозавантаження через `vendor/autoload.php`
- Використання PHPUnit\Framework\TestCase для всіх тестів
- Кольоровий вивід результатів
- Підтримка datasets та функціональних тестів
- Покриття коду для `src/` директорії

## Рекомендації

1. **Створіть резервну копію** ваших файлів перед використанням
2. **Перевірте права доступу** до тек призначення
3. **Використовуйте абсолютні шляхи** для уникнення помилок
4. **Файли з помилками** потребують ручної обробки
5. **Запускайте тести** після внесення змін у код
