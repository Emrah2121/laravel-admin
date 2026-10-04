# CLAUDE.md

# Laravel Project Rules
hər kod dəyişəndə layihəni dayandırma!

Bu layihədə kod yazarkən mövcud Laravel strukturuna və mövcud coding style-a uyğun işləməlisən.

Yeni kod yazmazdan əvvəl əlaqəli mövcud faylları oxu və həmin strukturun pattern-ini davam etdir.

---

## Architecture

Layihənin əsas strukturu:

```text
Route
→ Controller
→ FormRequest
→ Model
→ Database
→ Blade
```

Controller ilə Model arasında əlavə layer yaratma.

Business logic mümkün qədər mövcud Controller və Model strukturunda sadə şəkildə həll edilməlidir.

Layihədə mövcud olan aşağıdakı xüsusi Service-lər yalnız öz məqsədləri üçün istifadə olunur:

* Image/File Upload Service
* SEO Service
* Translation Service

Yeni Service yaratma.

---

# Controller

Controller sadə və oxunaqlı olmalıdır.

Controller-də:

* validation qaydaları yazma
* `Request::validate()` istifadə etmə
* Blade daxilində edilməli presentation logic yazma
* lazımsız abstraction yaratma

Validation FormRequest-də olmalıdır.

Sadə database əməliyyatlarında Eloquent Model istifadə edilə bilər.

Məsələn:

```php
public function store(StoreBlogRequest $request)
{
    $blog = Blog::create($request->validated());

    return redirect()
        ->route('blogs.index')
        ->with('success', 'Blog uğurla yaradıldı.');
}
```

---

# FormRequest

Bütün form validation-ları FormRequest class-larında yaz.

Məsələn:

```text
StoreBlogRequest
UpdateBlogRequest
StoreProjectRequest
UpdateProjectRequest
```

Controller daxilində:

```php
$request->validate(...)
```

yazma.

FormRequest:

```php
public function rules(): array
{
    return [
        'title' => ['required', 'string', 'max:255'],
    ];
}
```

şəklində olmalıdır.

Mövcud layihənin validation message və attribute sistemindən istifadə et.

---

# Model

Database əməliyyatlarında Eloquent Model istifadə et.

Model daxilində:

* `$fillable`
* `$casts`
* relationships
* scopes
* accessors
* mutators
* constants

və modelə aid digər logic ola bilər.

Məsələn:

```php
class Blog extends Model
{
    protected $fillable = [
        'slug',
        'status',
    ];

    public function translations()
    {
        return $this->hasMany(BlogTranslation::class);
    }
}
```

---

# Eloquent

Eloquent query-lərini düzgün və oxunaqlı yaz.

Lazım olduqda eager loading istifadə et:

```php
Blog::with('translations')->get();
```

N+1 query yaratma.

Lazımsız `all()` istifadə etmə.

Pagination lazım olan səhifələrdə:

```php
->paginate(15)
```

istifadə et.

Yalnız lazım olan column-ları seçmək mümkündürsə:

```php
->select(['id', 'title'])
```

istifadə et.

---

# Blade

Blade yalnız presentation/UI layer olmalıdır.

## Qəti qayda

Blade daxilində `@php` istifadə etmə.

Bu qadağandır:

```blade
@php
    $something = ...
@endphp
```

Blade daxilində:

* database query
* Model query
* Service çağırışı
* complex data processing
* business logic
* böyük PHP kodu

yazma.

View üçün lazım olan məlumatları Controller hazırlamalıdır.

---

## Blade-də sadə presentation logic

Sadə `if`, `foreach`, `isset`, `empty` və presentation məqsədli şərtlər istifadə edilə bilər.

Məsələn:

```blade
@if($blog->status)
    <span class="badge bg-success">Active</span>
@endif
```

Amma data hazırlamaq üçün Blade-də PHP yazma.

---

# Image / File Upload

Layihədə mövcud Image/File Upload Service varsa, həmin Service-dən istifadə et.

Upload logic-i hər Controller-də yenidən yazma.

Mövcud upload sistemini dəyişmə və yeni upload abstraction yaratma.

---

# SEO

Layihədə mövcud SEO sistemi və SEO Service istifadə olunmalıdır.

SEO logic-i Blade daxilində yazma.

Yeni SEO sistemi və ya əlavə package yaratma/əlavə etmə.

Mövcud SEO implementation-ını davam etdir.

---

# Translation

Layihədə mövcud Translation sistemi istifadə olunmalıdır.

Translation məlumatlarını Blade daxilində hazırlama.

Mövcud Translation Service, Trait, Model və database strukturundan istifadə et.

Yeni translation architecture yaratma.

---

# Routes

Route daxilində business logic yazma.

Yanlış:

```php
Route::get('/blogs', function () {
    return Blog::all();
});
```

Controller istifadə et.

Route naming və mövcud route strukturunu qoruyub saxla.

---

# Database

Database dəyişiklikləri migration vasitəsilə edilməlidir.

Migration yazmazdan əvvəl mövcud database strukturunu yoxla.

Mövcud table və column-ları səbəbsiz dəyişmə.

Foreign key, index və nullable vəziyyətlərini mövcud layihənin strukturuna uyğun yaz.

---

# Seeder

Seeder-ləri mövcud database strukturuna uyğun yaz.

Duplicate data yaratmamağa diqqət et.

Mövcud Seeder pattern-ini davam etdir.

---

# Naming

Laravel naming convention istifadə et.

Class:

```text
BlogController
Blog
StoreBlogRequest
UpdateBlogRequest
```

Methods:

```text
index()
create()
store()
show()
edit()
update()
destroy()
```

Variable adları aydın olmalıdır:

```php
$blog
$blogs
$translation
$translations
```

Mənasız dəyişən adlarından istifadə etmə:

```php
$x
$temp
$abc
$data1
```

---

# Clean Code

Kod:

* sadə
* oxunaqlı
* maintainable
* reusable
* təhlükəsiz

olmalıdır.

DRY prinsipinə əməl et.

Eyni kodu lazımsız şəkildə təkrarlama.

Amma kiçik kod üçün lazımsız class, Service və abstraction yaratma.

**Over-engineering etmə.**

Ən sadə düzgün Laravel həllini seç.

---

# Existing Code First

Yeni feature yazmazdan əvvəl layihədə oxşar feature tap.

Məsələn Blog yazırsansa:

```text
mövcud CRUD
mövcud Controller
mövcud Request
mövcud Model
mövcud Blade
```

strukturlarını oxu.

Yeni feature həmin struktura uyğun olmalıdır.

Mövcud project architecture-ını səbəbsiz dəyişmə.

---

# Modification Rules

Mən dəyişiklik istəyəndə:

1. Əvvəl əlaqəli faylları oxu.
2. Mövcud implementation-u başa düş.
3. Hansı faylların istifadə olunduğunu yoxla.
4. Lazım olan minimum dəyişiklikləri et.
5. Mövcud funksionallığı pozma.
6. Lazımsız refactor etmə.
7. Lazımsız dependency/package əlavə etmə.
8. Mövcud naming və coding style-ı qoruyub saxla.

---

# Deletion Rules

Bir feature silinirsə, onun bütün istifadələrini yoxla.

Yoxla:

```text
Controller
Request
Model
Migration
Seeder
Routes
Blade
JavaScript
CSS
Translations
```

İstifadəsiz kodları təmizlə.

Başqa feature-lərə aid işləyən kodlara toxunma.

---

# Security

User input-u həmişə validate et.

Mass assignment qorumasını saxla.

Authorization tələb olunan əməliyyatlarda layihədə mövcud authorization sistemindən istifadə et.

User input-u təhlükəli şəkildə HTML kimi render etmə.

Raw SQL yalnız həqiqətən lazım olduqda istifadə et.

---

# Code Style

Laravel və PHP coding standard-larına uyğun kod yaz.

Kodda lazımsız comment yazma.

Comment yalnız kodun nə etdiyini yox, niyə belə edildiyini izah etmək lazım olduqda istifadə et.

Uzun və qarışıq method-lar yaratma.

Method-lar mümkün qədər bir məsuliyyət daşımalıdır.

---

# Before Finishing

Kod dəyişikliklərindən sonra:

* PHP syntax yoxla
* import-ları yoxla
* route-ları yoxla
* FormRequest validation-u yoxla
* Model relationships-i yoxla
* Blade syntax-ını yoxla
* N+1 query ehtimalını yoxla
* mövcud funksionallığın pozulmadığını yoxla

Əlaqəli testlər varsa işə sal.

---

# IMPORTANT

Mən xüsusi olaraq istəmədiyim halda:

* yeni Service yaratma
* yeni architecture yaratma
* yeni abstraction yaratma
* lazımsız package əlavə etmə
* DTO/Repository/Action/UseCase kimi əlavə layer yaratma
* Blade-də `@php` istifadə etmə
* Controller-də validation yazma
* Blade-də database query yazma

**Əvvəl mövcud kodu oxu, sonra layihənin mövcud strukturuna uyğun ən sadə və clean həlli implement et.**
