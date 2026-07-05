$ErrorActionPreference = "Stop"
Set-Location $PSScriptRoot\..

if (-not (Test-Path .git)) {
    git init
}

# Ensure runtime artifacts stay untracked
if (-not (Select-String -Path .gitignore -Pattern "phpunit.result.cache" -Quiet)) {
    Add-Content .gitignore "`n.phpunit.result.cache"
}

function Commit-Group {
    param([string]$Message, [string[]]$Paths)
    foreach ($p in $Paths) {
        if (Test-Path $p) {
            git add $p
        }
    }
    $pending = git diff --cached --name-only
    if ($pending) {
        git commit -m $Message
    }
}

# 1) Laravel foundation
Commit-Group "chore: initialize Laravel 12 generic application foundation" @(
    ".editorconfig",
    ".gitattributes",
    ".gitignore",
    ".env.example",
    "README.md",
    "artisan",
    "composer.json",
    "composer.lock",
    "package.json",
    "vite.config.js",
    "phpunit.xml",
    "bootstrap",
    "config/app.php",
    "config/auth.php",
    "config/cache.php",
    "config/data.php",
    "config/database.php",
    "config/filesystems.php",
    "config/logging.php",
    "config/mail.php",
    "config/queue.php",
    "config/sanctum.php",
    "config/services.php",
    "config/session.php",
    "routes/console.php",
    "public/index.php",
    "public/robots.txt",
    "public/.htaccess",
    "public/assets",
    "resources/js",
    "resources/css",
    "storage",
    "tests",
    "database/.gitignore",
    "database/factories",
    "database/migrations/0001_01_01_000000_create_users_table.php",
    "database/migrations/0001_01_01_000001_create_cache_table.php",
    "database/migrations/0001_01_01_000002_create_jobs_table.php",
    "database/migrations/2026_07_02_144523_create_personal_access_tokens_table.php",
    "app/Attributes",
    "app/Facades",
    "app/Helpers/ArrayHelper.php",
    "app/Helpers/AttributeHelper.php",
    "app/Helpers/DtoHelper.php",
    "app/Helpers/FormHelper.php",
    "app/Http/Controllers/Controller.php",
    "app/Http/Middleware",
    "app/Models/User.php",
    "app/Models/BaseModel.php",
    "app/Data/BaseData.php",
    "app/Repositories/BaseRepository.php",
    "app/Traits",
    "app/Support",
    "app/Providers",
    "app/View/Components/Alert.php",
    "app/View/Components/Card.php",
    "app/View/Components/DetailsView.php",
    "app/View/Components/Form.php",
    "app/View/Components/FormCard.php",
    "app/View/Components/Modal.php"
)

# 2) Dual-theme UI layer
Commit-Group "feat: add swappable Metronic 8 and Metronic 9 theme layer" @(
    "config/ui.php",
    "app/helpers.php",
    "app/View/Concerns",
    "app/View/Components/Button.php",
    "lang",
    "resources/views/template.blade.php",
    "resources/views/themes",
    "public/themes"
)

# 3) Shared navigation and generic CRUD page shells
Commit-Group "feat: add shared navigation and generic CRUD page templates" @(
    "config/navigation.php",
    "resources/views/README.md",
    "resources/views/pages"
)

# 4) Category module with API and DataTables
Commit-Group "feat: add Category CRUD with API endpoints and DataTable listing" @(
    "config/datatables.php",
    "routes/api.php",
    "routes/web.php",
    "app/Http/Controllers/BaseController.php",
    "app/Http/Controllers/Api/BaseApiController.php",
    "app/Http/Controllers/CategoryController.php",
    "app/Http/Controllers/Api/CategoryController.php",
    "app/Models/Category.php",
    "app/Repositories/CategoryRepository.php",
    "app/Data/CategoryData.php",
    "app/Data/CategoryViewData.php",
    "app/View/Components/Datatable.php",
    "database/migrations/2023_02_05_062401_create_category_table.php",
    "database/seeders"
)

# 5) Modal routing, deep links, and action menu UX
Commit-Group "feat: add modal routing with deep links and split edit actions" @(
    "app/Http/Controllers/Concerns",
    "app/Helpers/Ui.php"
)

git status --short
git log --oneline
