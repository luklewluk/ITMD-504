# Project structure and Azure setup

## Model
GPT 5.5 in ChatGPT

## Prompt
> As part of my Programming Foundations course, I need to create a web application for the final project. The frontend must communicate with the backend through an API. The application should be relatively simple, and it must support automatic deployment from GitHub.
I would like to use React for the frontend and Symfony for the backend. What would be the best way to structure and deploy this project? Are there any predefined GitHub Actions workflows available? Would it be better to use two separate repositories or a single repository?
Additionally, I have $100 in Azure credit through my student account.

## Response

The recommended approach is to use **one repository with two separate directories and two independent deployment workflows**:

```text
project/
  frontend/          # React, preferably using Vite
  api/               # Symfony API
  .github/workflows/
    deploy-frontend.yml
    deploy-api.yml
  README.md
```

### Recommended Architecture

* **Frontend:** Azure Static Web Apps
* **Backend:** Azure App Service for Linux with PHP and Symfony
* **Repository structure:** Monorepo
* **Deployment:** GitHub Actions triggered by pushes to the `main` branch

This is a clear architecture for a student project. It demonstrates proper separation between the frontend and backend, ensures that React communicates with Symfony through an HTTP API, and avoids the additional complexity of managing multiple repositories.

Azure Static Web Apps can be used to host the React application, while Azure App Service can host the Symfony API. Because PHP support in Azure App Service should be deployed on Linux, the backend should use a Linux-based App Service.

### One Repository or Two?

For this project, a **single repository** is the better choice.

A monorepo provides:

* one README file,
* one issue tracker,
* one commit history,
* easier project submission and review,
* simpler coordination between frontend and backend changes.

Two repositories are more appropriate when the frontend and backend are managed by separate teams, have independent release cycles, or are developed as completely separate products. Those advantages are not especially important for a small final project.

Even with a single repository, the frontend and backend can still be deployed independently. GitHub Actions can use path filters so that changes in `frontend/**` only trigger the frontend deployment, while changes in `api/**` only trigger the backend deployment.

### Available GitHub Actions

Azure provides predefined GitHub Actions for both services:

* `Azure/static-web-apps-deploy@v1` for deploying the React frontend to Azure Static Web Apps,
* `azure/webapps-deploy@v3` for deploying the Symfony backend to Azure App Service.

Azure Deployment Center can also generate a GitHub Actions workflow based on the selected application stack and automatically add it to the repository.

### Important Limitation of Azure Static Web Apps

Azure Static Web Apps supports integrated backend APIs, but those APIs are based primarily on Azure Functions. It does not directly host a full Symfony application.

For this reason, Azure Static Web Apps should only host the React frontend. The Symfony backend should be deployed separately to Azure App Service.

The React application can use an environment variable to store the backend URL:

```env
VITE_API_BASE_URL=https://your-api.azurewebsites.net
```

Example React request:

```ts
const API = import.meta.env.VITE_API_BASE_URL;

const response = await fetch(`${API}/api/tasks`);
```

### Example Frontend Deployment Workflow

For a React application created with Vite, the build output directory is normally `dist`.

```yaml
name: Deploy Frontend

on:
  push:
    branches: [main]
    paths:
      - "frontend/**"
      - ".github/workflows/deploy-frontend.yml"

jobs:
  build_and_deploy:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Deploy to Azure Static Web Apps
        uses: Azure/static-web-apps-deploy@v1
        with:
          azure_static_web_apps_api_token: ${{ secrets.AZURE_STATIC_WEB_APPS_API_TOKEN }}
          repo_token: ${{ secrets.GITHUB_TOKEN }}
          action: "upload"
          app_location: "frontend"
          api_location: ""
          output_location: "dist"
        env:
          VITE_API_BASE_URL: ${{ secrets.VITE_API_BASE_URL }}
```

The following GitHub secrets would be required:

* `AZURE_STATIC_WEB_APPS_API_TOKEN`
* `VITE_API_BASE_URL`

### Example Symfony Deployment Workflow

A typical Symfony deployment process includes:

* installing Composer dependencies,
* optimizing the autoloader,
* clearing and warming up the production cache,
* deploying the application to Azure App Service.

```yaml
name: Deploy Symfony API

on:
  push:
    branches: [main]
    paths:
      - "api/**"
      - ".github/workflows/deploy-api.yml"

jobs:
  build:
    runs-on: ubuntu-latest

    steps:
      - uses: actions/checkout@v4

      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: "8.3"
          extensions: intl, pdo_mysql, mbstring, zip
          coverage: none

      - name: Install dependencies
        working-directory: api
        run: composer install --no-dev --optimize-autoloader --no-interaction

      - name: Prepare Symfony cache
        working-directory: api
        run: |
          php bin/console cache:clear --env=prod
          php bin/console cache:warmup --env=prod

      - name: Deploy to Azure Web App
        uses: azure/webapps-deploy@v3
        with:
          app-name: "YOUR-AZURE-APP-SERVICE-NAME"
          publish-profile: ${{ secrets.AZURE_WEBAPP_PUBLISH_PROFILE }}
          package: api
```

Production secrets and configuration values should be stored in Azure App Service settings instead of being committed to the repository.

Example environment variables:

```text
APP_ENV=prod
APP_SECRET=...
DATABASE_URL=...
CORS_ALLOW_ORIGIN=https://your-frontend.azurestaticapps.net
```

### Symfony Public Directory

Symfony applications must be served from the `public/` directory, which contains the `index.php` entry point.

This is an important configuration detail when deploying Symfony to Azure App Service. The web server must use the Symfony `public/` directory as the application document root.

A simple health-check endpoint should also be added:

```php
#[Route('/api/health', methods: ['GET'])]
public function health(): JsonResponse
{
    return $this->json(['status' => 'ok']);
}
```

After deployment, it can be tested using:

```text
https://your-api.azurewebsites.net/api/health
```

### Suggested Project Scope

The safest project scope would be a small CRUD application. Possible examples include:

* a task tracker,
* a recipe manager,
* a reading or movie watchlist,
* an expense tracker.

A basic API could include the following endpoints:

```text
GET    /api/items
GET    /api/items/{id}
POST   /api/items
PUT    /api/items/{id}
DELETE /api/items/{id}
```

The Symfony backend can use manually created JSON controllers or API Platform. API Platform can generate an API-first structure, CRUD operations, and OpenAPI or Swagger documentation with relatively little configuration.

### Database Options

The most production-like solution would be:

* Azure Database for PostgreSQL, or
* Azure Database for MySQL.

For a very small demonstration project, SQLite may be simpler, provided that it is accepted by the instructor. However, using a managed database would better demonstrate a realistic cloud architecture.

### Azure Student Credit

The $100 Azure student credit should be sufficient for a small project if resources are kept minimal.

A cost-conscious setup would be:

1. Use the free tier of Azure Static Web Apps for the frontend.
2. Use the smallest suitable Azure App Service plan for the backend.
3. Use the smallest possible database configuration.
4. Monitor costs regularly in Azure Cost Management.
5. Delete the entire Azure resource group after the project has been graded.

### Final Recommendation

The project should use:

1. A monorepo with `frontend/` and `api/` directories.
2. React with Vite for the frontend.
3. Symfony as a REST API.
4. Azure Static Web Apps for the frontend.
5. Azure App Service for Linux for the backend.
6. Two separate GitHub Actions workflows.
7. CORS configuration allowing requests only from the deployed frontend.
8. A small CRUD-based project scope.
9. A README containing the architecture, deployment process, application URLs, screenshots, and API documentation.

This approach satisfies the requirement that the frontend communicates with the backend through an API, demonstrates automatic deployment with GitHub Actions, and remains manageable for a final course project.
