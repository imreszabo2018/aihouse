import { useEffect, useState } from 'react'
import StatCard from '../components/StatCard'
import InvoiceList from '../components/InvoiceList'
import { getInvoices, uploadInvoice } from '../services/api'

type Invoice = {
    id: number
    invoice_number: string | null
    gross_amount: string | null
    amount_huf: string | null
    currency: string | null
    direction: 'incoming' | 'outgoing' | null
    processing_status: string
    seller: { name: string } | null
    buyer: { name: string } | null
}

function Dashboard() {

    const [refreshKey, setRefreshKey] = useState(0)
    const [isUploading, setIsUploading] = useState(false)
    const [uploadMessage, setUploadMessage] = useState('')
    const [invoices, setInvoices] = useState<Invoice[]>([])

    useEffect(() => {
        async function loadInvoices() {
            try {
                const data: Invoice[] = await getInvoices()
                setInvoices(data)
            } catch (error) {
                console.error('Failed to load invoices:', error)
            }
        }

        loadInvoices()
    }, [refreshKey])

    const totalInvoices = invoices.length
    const incomingInvoices = invoices.filter(
        (invoice) => invoice.direction === 'incoming'
    ).length
    const outgoingInvoices = invoices.filter(
        (invoice) => invoice.direction === 'outgoing'
    ).length
    const totalAmountHuf = invoices.reduce(
        (sum, invoice) => sum + Number(invoice.amount_huf ?? 0),
        0
    )

    async function handleUpload(file: File) {
        setIsUploading(true)
        setUploadMessage('')

        try {
            const result = await uploadInvoice(file)

            console.log('Invoice uploaded successfully:', result)

            setRefreshKey((current) => current + 1)
            setUploadMessage('Invoice processed successfully!')
        } catch (error) {
            console.error('Upload error:', error)
            setUploadMessage('Failed to process invoice. Please try again.')
        } finally {
            setIsUploading(false)
        }
    }

    return (
        <section className="dashboard right-content">
            <div className="page-heading">

                <div>
                    <h1>Overview</h1>
                    <p>Monitor your invoices and financial activity.</p>
                </div>

                <div className="upload-container">
                    <label className={`primary-button ${isUploading ? 'uploading' : ''}`}>
                        {isUploading ? 'Processing...' : '+ Upload invoice'}

                        <input
                            type="file"
                            accept="application/pdf"
                            hidden
                            disabled={isUploading}
                            onChange={(event) => {
                                const file = event.target.files?.[0]

                                if (file) {
                                    handleUpload(file)
                                }

                                event.target.value = ''
                            }}
                        />
                    </label>

                    {isUploading && (
                        <p className="upload-message">
                            Processing invoice, please wait...
                        </p>
                    )}

                    {uploadMessage && (
                        <p className="upload-message">
                            {uploadMessage}
                        </p>
                    )}
                </div>
            </div>

            <div className="stats-grid">
                <StatCard
                    title="Total invoices"
                    value={String(totalInvoices)}
                    description="All processed invoices"
                />

                <StatCard
                    title="Incoming invoices"
                    value={String(incomingInvoices)}
                    description="Received from partners"
                />

                <StatCard
                    title="Outgoing invoices"
                    value={String(outgoingInvoices)}
                    description="Issued to customers"
                />

                <StatCard
                    title="Total amount (HUF)"
                    value={`${totalAmountHuf.toLocaleString('hu-HU', {
                        maximumFractionDigits: 0,
                    })} Ft`}
                    description="Converted invoice value"
                />
            </div>

            <section className="invoice-section">
                <div className="section-heading">
                    <div>
                        <h2>Recent invoices</h2>
                        <p>Your latest processed invoices.</p>
                    </div>

                    <button className="secondary-button">
                        View all
                    </button>
                </div>

                <InvoiceList refreshKey={refreshKey} />
            </section>
        </section>
    )
}

export default Dashboard
