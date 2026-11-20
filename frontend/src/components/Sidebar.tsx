import { NavLink } from 'react-router-dom'

function Sidebar() {
    return (
        <aside className="sidebar">
            <div className="brand">
                <span className="brand-icon">◆</span>
                <span>InvoiceFlow</span>
            </div>

            <nav className="navigation">
                <p className="nav-label">WORKSPACE</p>

                <NavLink to="/" className="nav-link">
                    <span>▦</span>
                    Dashboard
                </NavLink>

                <NavLink to="/invoices" className="nav-link">
                    <span>▤</span>
                    Invoices
                </NavLink>

                <a href="#" className="nav-link">
                    <span>♙</span>
                    Partners
                </a>

                <p className="nav-label">SETTINGS</p>

                <a href="#" className="nav-link">
                    <span>⚙</span>
                    Settings
                </a>
            </nav>

            <div className="sidebar-footer">
                InvoiceFlow v1.0
            </div>
        </aside>
    )
}

export default Sidebar
