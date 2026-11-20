
import { useLocation } from 'react-router-dom'

function Topbar() {
    const location = useLocation()

    const isInvoicesPage = location.pathname === '/invoices'

    const title = isInvoicesPage ? 'Invoices' : 'Dashboard'

    const subtitle = isInvoicesPage
        ? 'Manage and review your invoices.'
        : "Welcome back! Here's your invoice overview."

    return (
        <header className="topbar">
            <div>
                <h2>{title}</h2>
                <p>{subtitle}</p>
            </div>

            <div className="user-avatar">IM</div>
        </header>
    )
}

export default Topbar
