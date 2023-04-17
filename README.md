<style>
    body {
        text-align: justify;
    }
</style>

# E-commerce API
This is an API for an e-commerce website that allows users to browse products,
add items to their cart, and place orders.

## Prerequisites
To use this API, you will need:

- PHP 8.1.10 or later
- Composer
- MariaDB 10.5.8 or later
- A web server (e.g. Apache, Nginx)

## Installation
1. Use the package manager
[composer](https://getcomposer.org/)
to install all dependencies :
```shell
composer install
```
2. Create a new database by using Symfony Doctrine command :
```shell
symfony console doctrine:database:create
```
3. Copy `.env` file and update the database connection information.
4. Run migrate command using Symfony Doctrine
to create the database schema :
```shell
symfony console doctrine:migrations:migrate
```
5. Start the web server and navigate to the project in your web browser :
```shell
symfony server:start
```

## Bearer Token
This API uses a bearer token for authentication. To obtain a token, send a POST request
to the /api/login endpoint with your username and password in the request body.
The API will respond with a token that you can use for subsequent requests.

## API Endpoints

### Authentication
- POST /api/register: Register a new user
- POST /api/login: Login to obtain authentication token

### Users
- PUT /api/users: Update current user information
- GET /api/users: Display current user information

### Products
- GET /api/products: Get list of products
- GET /api/products/{productId}: Get informations of a product
- POST /api/products: Add a product
- PUT /api/products/{productId}: Modify a product
- DELETE /api/products/{productId}: Delete a product

### Carts
- POST /api/carts/{productId}: Add product to shopping cart
- DELETE /api/carts/{productId}: Remove product from shopping cart
- GET /api/carts: Get the state of the shopping cart
- POST /api/carts/validate: Validate the shopping cart

### Orders
- GET /api/orders: Get all orders of current user
- GET /api/orders/{orderId}: Get information about an order

# END