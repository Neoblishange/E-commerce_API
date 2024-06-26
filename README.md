# E-commerce API
This is an API for an e-commerce website that allows users to browse products,
add items to their cart, and place orders.

## Prerequisites
To use this API, you will need:

- PHP 8.1.10 or later
- Symfony 5.5.2
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
4. Run the following commands using Symfony Doctrine
to create the database :
```shell
symfony console doctrine:database:create
symfony console make:migration
symfony console doctrine:migrations:migrate
```
5. Once you created the database, run this SQL command :
```sql
ALTER TABLE e_commerce.order_product ADD COLUMN quantity INT
```
6. Start the web server and navigate to the project in your web browser :
```shell
symfony server:start
```

## Bearer Token
This API uses a bearer token for authentication. To obtain a token, send a POST request
to the /api/login endpoint with your username and password in the request body.
The API will respond with a token that you can use for subsequent requests.

## API Endpoints

|                | Method | AUTH | Endpoint                  | Description                          |
|----------------|--------|------|---------------------------|--------------------------------------|
| Authentication | POST   | NO   | /api/register             | Register a new user                  |
|                | POST   | NO   | /api/login                | Login to obtain authentication token |
| Users          | PUT    | YES  | /api/users                | Update current user information      |
|                | GET    | YES  | /api/users                | Display current user information     |
| Products       | GET    | NO   | /api/products             | Get list of products                 |
|                | GET    | NO   | /api/products/{productId} | Get informations of a product        |
|                | POST   | YES  | /api/products             | Add a product                        |
|                | PUT    | YES  | /api/products/{productId} | Modify a product                     |
|                | DELETE | YES  | /api/products/{productId} | Delete a product                     |
| Carts          | POST   | YES  | /api/carts/{productId}    | Add product to shopping cart         |
|                | DELETE | YES  | /api/carts/{productId}    | Remove product from shopping cart    |
|                | GET    | YES  | /api/carts                | Get the state of the shopping cart   |
|                | POST   | YES  | /api/carts/validate       | Validate the shopping cart           |
| Orders         | GET    | YES  | /api/orders               | Get all orders of current user       |
|                | GET    | YES  | /api/orders/{orderId}     | Get information about an order       |


# END