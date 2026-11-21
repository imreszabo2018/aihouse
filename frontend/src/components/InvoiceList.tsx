
import { useEffect, useState } from 'react'
import { getInvoices } from '../services/api'

type Partner = {
  name: string
}

type Invoice = {
  id: number
  invoice_number: string | null
  issue_date: string | null
  fulfillment_date: string | null
  due_date: string | null
  gross_amount: string | null
  currency: string | null
  exchange_rate: string | null
  exchange_rate_date: string | null
  amount_huf: string | null
  direction: 'incoming' | 'outgoing' | null
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

        const formatDate = (date: string | null) =>
          date ? new Date(date).toLocaleDateString('hu-HU') : '—'

        const formatAmount = (amount: string | null) =>
          amount !== null
            ? Number(amount).toLocaleString('hu-HU', {
              maximumFractionDigits: 2,
            })
            : '—'

        return (
          <div className="invoice-row" key={invoice.id}>
            <div>
              <strong>{invoice.invoice_number ?? 'Unknown invoice'}</strong>
              <p>{partner}</p>
              <p>
                Issue date: {formatDate(invoice.issue_date)}
              </p>
              <p>
                Fulfillment date: {formatDate(invoice.fulfillment_date)}
              </p>
              <p>
                Due date: {formatDate(invoice.due_date)}
              </p>
            </div>

            <div>
              <p>
                Original amount: {formatAmount(invoice.gross_amount)}{' '}
                {invoice.currency ?? ''}
              </p>
              <p>
                Exchange rate: {formatAmount(invoice.exchange_rate)}
              </p>
              <p>
                Exchange rate date: {formatDate(invoice.exchange_rate_date)}
              </p>
              <p>
                HUF amount: {formatAmount(invoice.amount_huf)} Ft
              </p>
            </div>

            <div>
              {invoice.direction && (
                <span className={`direction-badge ${invoice.direction}`}>
                  {invoice.direction === 'incoming' ? 'Incoming' : 'Outgoing'}
                </span>
              )}

              <p>
                <span className={`status-badge ${invoice.processing_status}`}>
                  {invoice.processing_status === 'processed'
                    ? 'Processed'
                    : invoice.processing_status === 'failed'
                      ? 'Failed'
                      : 'Pending'}
                </span>
              </p>
            </div>
          </div>
        )
      })}
    </div>
  )
}

export default InvoiceList
