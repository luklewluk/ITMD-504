import { useEffect, useState } from 'react'
import Container from 'react-bootstrap/Container'
import Row from 'react-bootstrap/Row'
import Col from 'react-bootstrap/Col'
import Card from 'react-bootstrap/Card'
import Form from 'react-bootstrap/Form'
import Button from 'react-bootstrap/Button'
import './App.css'
import cartIcon from './assets/cart.svg'

function App() {
  const [products, setProducts] = useState([])
  const items = []

  useEffect(() => {
    fetch(import.meta.env.VITE_API_BASE_URL + '/api/products')
      .then((r) => r.json())
      .then(setProducts)
  }, [])

  const completedCount = items.filter((item) => item.completed).length

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
            <Form.Control placeholder="+ Add custom product..." disabled />
            <Button variant="success" disabled>
              Add
            </Button>
          </div>

          <p className="text-muted text-uppercase small mt-3 mb-2">Suggested items</p>
          <div className="d-flex flex-wrap gap-2">
            {products.map((item) => (
              <Button key={item} variant="outline-secondary" size="sm" disabled>
                + {item}
              </Button>
            ))}
          </div>
        </Card.Body>
      </Card>

      {items.map((item) => (
        <div
          key={item.id}
          className="d-flex align-items-center gap-2 p-3 mb-2 bg-white border rounded"
        >
          <Form.Check checked={item.completed} readOnly />
          <span className={item.completed ? 'text-muted text-decoration-line-through' : ''}>
            {item.name}
          </span>
        </div>
      ))}
    </Container>
  )
}

export default App
