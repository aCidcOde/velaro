# API Mobile

## Base URL

Todas as rotas mobile vivem sob:

```text
/api/mobile
```

## Headers

```http
Authorization: Bearer {token}
Accept: application/json
Content-Type: application/json
```

## Autenticação

- `POST /api/mobile/auth/register`
- `POST /api/mobile/auth/login`
- `POST /api/mobile/auth/forgot-password`
- `POST /api/mobile/auth/reset-password`
- `GET /api/mobile/auth/me`
- `POST /api/mobile/auth/logout`

## Recursos autenticados

### Dashboard

- `GET /api/mobile/dashboard`

### Customers

- `GET /api/mobile/customers`
- `POST /api/mobile/customers`
- `GET /api/mobile/customers/{customer}`
- `PUT|PATCH /api/mobile/customers/{customer}`
- `DELETE /api/mobile/customers/{customer}`

### Products

- `GET /api/mobile/products`
- `POST /api/mobile/products`
- `GET /api/mobile/products/{product}`
- `PUT|PATCH /api/mobile/products/{product}`
- `DELETE /api/mobile/products/{product}`

### Orders

- `GET /api/mobile/orders`
- `POST /api/mobile/orders`
- `GET /api/mobile/orders/{order}`
- `PUT|PATCH /api/mobile/orders/{order}`
- `DELETE /api/mobile/orders/{order}`

## Regras de acesso

- cada token enxerga apenas os recursos do próprio usuário
- recursos de outro usuário retornam `404`
- o payload mobile segue o mesmo domínio de clientes, produtos e pedidos usado no front

## Objetivo da camada mobile

A API mobile da versão `1.0` entrega o essencial para operar a base em aplicativos externos sem duplicar regras do core.
