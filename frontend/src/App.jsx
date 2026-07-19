import { useEffect, useState } from 'react'
import Container from 'react-bootstrap/Container'
import Row from 'react-bootstrap/Row'
import Col from 'react-bootstrap/Col'
import Card from 'react-bootstrap/Card'
import Form from 'react-bootstrap/Form'
import Button from 'react-bootstrap/Button'
import './App.css'
import cartIcon from './assets/cart.svg'

const API = import.meta.env.VITE_API_BASE_URL

function App() {
  const [products, setProducts] = useState([])
  const [items, setItems] = useState([])
  const [newName, setNewName] = useState('')
  const [newPrice, setNewPrice] = useState('')

  function loadProducts() {
    fetch(API + '/api/products')
      .then((r) => r.json())
      .then(setProducts)
  }

  function loadItems() {
    fetch(API + '/api/shopping-list-items')
      .then((r) => r.json())
      .then(setItems)
  }

  useEffect(() => {
    loadProducts()
    loadItems()
  }, [])

  function addToList(productId) {
    fetch(API + '/api/add-item-by-product/' + productId, { method: 'POST' })
      .then(loadItems)
  }

  function toggleCheck(itemId) {
    fetch(API + '/api/check-item/' + itemId, { method: 'PUT' })
      .then(loadItems)
  }

  function changeQuantity(itemId, quantity) {
    if (quantity < 1) return
    fetch(API + '/api/change-item-quantity/' + itemId + '/' + quantity, { method: 'PUT' })
      .then(loadItems)
  }

  function deleteItem(itemId) {
    fetch(API + '/api/delete-shopping-list-item/' + itemId, { method: 'DELETE' })
      .then(loadItems)
  }

  function createProduct() {
    fetch(API + '/api/products', {
      method: 'POST',
      headers: { 'Content-Type': 'application/json' },
      body: JSON.stringify({ name: newName, price: newPrice }),
    }).then(() => {
      setNewName('')
      setNewPrice('')
      loadProducts()
    })
  }

  function deleteProduct(productId) {
    fetch(API + '/api/product/' + productId, { method: 'DELETE' })
      .then(loadProducts)
  }

  const completedCount = items.filter((item) => item.is_checked).length

  return (
    <Container className="py-4 shopping-list">
      <header className="border-bottom pb-3 mb-4">
        <Row>
          <Col>
            <h1 className="h4 mb-1 d-flex align-items-center gap-2"><img
                src={cartIcon}
                alt=""
                aria-hidden="true"
                width="24"
                height="24"
            /> Shopping List</h1>
            <p className="text-muted mb-0">Keep track of your groceries</p>
          </Col>
          <Col xs="auto" className="text-muted">
            <strong className="text-success">{completedCount}</strong> / {items.length}
          </Col>
        </Row>
      </header>

      <Card className="mb-4">
        <Card.Body>
          <div className="d-flex gap-2">
            <Form.Control
              placeholder="Product name..."
              value={newName}
              onChange={(e) => setNewName(e.target.value)}
            />
            <Form.Control
              placeholder="Price"
              value={newPrice}
              onChange={(e) => setNewPrice(e.target.value)}
              style={{ maxWidth: '120px' }}
            />
            <Button variant="success" onClick={createProduct}>
              Add
            </Button>
          </div>

          <p className="text-muted text-uppercase small mt-3 mb-2">Suggested items</p>
          <div className="d-flex flex-wrap gap-2">
            {products.map((product) => (
              <div key={product.id} className="btn-group" role="group">
                <Button
                  variant="outline-secondary"
                  size="sm"
                  onClick={() => addToList(product.id)}
                >
                  + {product.name}
                </Button>
                <Button
                  variant="outline-danger"
                  size="sm"
                  onClick={() => deleteProduct(product.id)}
                >
                  ×
                </Button>
              </div>
            ))}
          </div>
        </Card.Body>
      </Card>

      {items.map((item) => (
        <div
          key={item.id}
          className="d-flex align-items-center gap-2 p-3 mb-2 bg-white border rounded"
        >
          <Form.Check
            checked={item.is_checked}
            onChange={() => toggleCheck(item.id)}
          />
          <span className={item.is_checked ? 'text-muted text-decoration-line-through' : ''}>
            {item.product_name}
          </span>
          <span className="text-muted small">${item.product_price}</span>

          <div className="ms-auto d-flex align-items-center gap-2">
            <Button
              variant="outline-secondary"
              size="sm"
              onClick={() => changeQuantity(item.id, item.quantity - 1)}
            >
              −
            </Button>
            <span>{item.quantity}</span>
            <Button
              variant="outline-secondary"
              size="sm"
              onClick={() => changeQuantity(item.id, item.quantity + 1)}
            >
              +
            </Button>
            <Button
              variant="outline-danger"
              size="sm"
              onClick={() => deleteItem(item.id)}
            >
              Delete
            </Button>
          </div>
        </div>
      ))}
    </Container>
  )
}

export default App
