# Music Resort/Deduplicate Tool

Консольні команди для автоматичного сортування музичнх файлів (mp3, flac, m4a) за виконавцями в окремі теки та
дедублікації аудіо.

## Встановлення

1. Встановіть залежності:

```
composer install
```

1. Зробіть консольний файл виконуваним (Linux/Mac):

```
chmod +x bin/console
```

## Використання

- ### Розсортування музики по теках виконавців

    ```
    php bin/console mp3:resort <source_directory> <destination_directory>
    ```

    - **Windows:**

      ```
      php bin/console mp3:resort "C:\Music\Unsorted" "C:\Music\Sorted"
      ```

    - **Linux/Mac:**

      ```
      php bin/console mp3:resort "/home/user/music/unsorted" "/home/user/music/sorted"
      ```

- ### Дедублікація музики в теці

    ```
    php bin/console mp3:deduplicate <source_directory>
    ```

    - **Windows:**

      ```
      php bin/console mp3:deduplicate "C:\Music\Unsorted"
      ```

    - **Linux/Mac:**

      ```
      php bin/console mp3:deduplicate "/home/user/music/unsorted"
      ```

- ### Виправлення розширень файлів за метаданими

    ```
    php bin/console files:fix-extensions <source_directory> [--dry-run]
    ```

    - **Windows:**

      ```
      php bin/console files:fix-extensions "C:\Music\Unsorted" --dry-run
      ```

    - **Linux/Mac:**

      ```
      php bin/console files:fix-extensions "/home/user/music/unsorted" --dry-run
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
│   └── console                         # Точка входу консольної програми
├── src/
│   ├── Command/
│   │   ├── ResortMp3Command.php        # Команда сортування
│   │   └── DeduplicateMp3Command.php   # Команда дедублікації
│   ├── Service/
│   │   ├── Mp3ResortService.php        # Логіка сортування
│   │   └── Mp3DeduplicateService.php   # Логіка дедублікації
│   └── ...
├── composer.json                       # Залежності проекту
└── README.md                           # Документація
```

## Залежності

- **PHP 8.4+** - мінімальна версія PHP
- **symfony/console** — консольний інтерфейс
- **symfony/finder** — пошук файлів
- **symfony/filesystem** — операції з файловою системою

## Локалізація

- Усі повідомлення локалізовані через глобальну функцію __() і файли перекладів у каталозі `lang` (напр.,
  `lang/en/console.php`).
- Базова локаль за замовчуванням — `en` (див. `config/app.php` → `default_lang`).
- Змінити локаль можна через .env: `DEFAULT_LANG=uk`, або на рівні коду:
  `\Root\MusicLocal\Service\LocalizationService::setLocale('uk')`.
- Додавання нової мови: створіть директорію `lang/<locale>/` і файл перекладу `console.php` з тими ж ключами.

Приклад .env:

```dotenv
# Примусово ввімкнути dry-run для всіх команд
DEBUG=true
# Локаль інтерфейсу команд
DEFAULT_LANG=uk
```

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

## Додаткові можливості

### Перегляд довідки:

```
php bin/console mp3:resort --help
php bin/console mp3:deduplicate --help
```

### Режим симуляції:

```
php bin/console mp3:resort <source> <destination> --dry-run
php bin/console mp3:deduplicate <source> --dry-run
```

### Перегляд версії:

```
php bin/console --version
```

### Список доступних команд:

```
php bin/console list
```

## Тестування (планується)

Наразі автотести відсутні в цьому репозиторії. Нижче описано плановану структуру та сценарії тестів, які можуть бути
додані пізніше.

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

```
composer install
```

1. **Запустіть всі тести:**

    ```
    php vendor/bin/pest
    ```

2. **Запустіть тільки модульні тести:**

    ```
    php vendor/bin/pest tests/Unit
    ```

3. **Запустіть тільки інтеграційні тести:**

    ```
    php vendor/bin/pest tests/Integration
    ```

4. **Запустіть тести з детальним виводом:**

    ```
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
