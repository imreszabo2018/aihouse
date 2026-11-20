
import { useEffect, useState } from 'react'
import { getInvoices } from '../services/api'

type Partner = {
  name: string
}

type Invoice = {
  id: number
  invoice_number: string
  issue_date: string | null
  gross_amount: string
  currency: string
  direction: 'incoming' | 'outgoing'
  processing_status: string
  seller?: Partner | null
  buyer?: Partner | null
}

type InvoiceListProps = {
  refreshKey: number
}

function InvoiceList({ refreshKey }: InvoiceListProps) {

  const [invoices, setInvoices] = useState<Invoice[]>([])
  const [loading, setLoading] = useState(true)
  const [error, setError] = useState('')

  useEffect(() => {
    async function loadInvoices() {
      try {
        const data: Invoice[] = await getInvoices()
        setInvoices(data)
      } catch {
        setError('Failed to load invoices.')
      } finally {
        setLoading(false)
      }
    }

    loadInvoices()
  }, [refreshKey])

  if (loading) {
    return <p>Loading invoices...</p>
  }

  if (error) {
    return <p>{error}</p>
  }

  if (invoices.length === 0) {
    return <p>No invoices yet.</p>
  }

  return (
    <div className="invoice-list">
      {invoices.map((invoice) => {
        const partner =
          invoice.direction === 'incoming'
            ? invoice.seller?.name ?? 'Unknown partner'
            : invoice.buyer?.name ?? 'Unknown partner'

        return (
          <div className="invoice-row" key={invoice.id}>

            <div>
              <strong>{invoice.invoice_number}</strong>
              <p>{partner}</p>
              <p>
                {invoice.issue_date
                  ? new Date(invoice.issue_date).toLocaleDateString('hu-HU')
                  : 'No date'}
              </p>
              {invoice.direction && (
                <span className={`direction-badge ${invoice.direction}`}>
                  {invoice.direction === 'incoming'
                    ? 'Incoming'
                    : 'Outgoing'}
                </span>
              )}
            </div>

            <span>
              {Number(invoice.gross_amount).toLocaleString('hu-HU')}{' '}
              {invoice.currency}
            </span>

            <span className={`status-badge ${invoice.processing_status}`}>
              {invoice.processing_status === 'processed'
                ? 'Processed'
                : invoice.processing_status === 'failed'
                  ? 'Failed'
                  : 'Pending'}
            </span>
          </div>
        )
      })}
    </div>
  )
}

export default InvoiceList
