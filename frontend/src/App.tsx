import { Routes, Route } from 'react-router-dom'
import Sidebar from './components/Sidebar'
import Topbar from './components/Topbar'
import Dashboard from './pages/Dashboard'
import './App.css'
import Invoices from './pages/Invoices'

function App() {
  return (
    <div className="app">
      <Sidebar />

      <main className="main-content">
        <Topbar />

        <Routes>
          <Route path="/" element={<Dashboard />} />
          <Route path="/invoices" element={<Invoices />} />
        </Routes>
      </main>
    </div>
  )
}

export default App
