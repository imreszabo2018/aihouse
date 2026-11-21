import { useEffect, useState } from 'react'
import StatCard from '../components/StatCard'
import InvoiceList from '../components/InvoiceList'
import { getInvoices, uploadInvoice } from '../services/api'
import {
    ResponsiveContainer,
    LineChart,
    Line,
    XAxis,
    YAxis,
    CartesianGrid,
    Tooltip,
    Legend,
} from 'recharts'

type Invoice = {
    id: number
    invoice_number: string | null
    due_date: string | null
    gross_amount: string | null
    amount_huf: string | null
    currency: string | null
    direction: 'incoming' | 'outgoing' | null
    processing_status: string
    seller: {
        name: string
        tax_number: string | null
    } | null

    buyer: {
        name: string
        tax_number: string | null
    } | null
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

    const processedInvoices = invoices.filter(
        (invoice) => invoice.processing_status === 'processed'
    )

    const totalInvoices = processedInvoices.length
    const incomingInvoices = processedInvoices.filter(
        (invoice) => invoice.direction === 'incoming'
    ).length
    const outgoingInvoices = processedInvoices.filter(
        (invoice) => invoice.direction === 'outgoing'
    ).length
    const totalAmountHuf = processedInvoices.reduce(
        (sum, invoice) => sum + Math.round(Number(invoice.amount_huf ?? 0)),
        0
    )
    const monthlyCashFlow = processedInvoices.reduce((months, invoice) => {
        if (!invoice.due_date || !invoice.direction) {
            return months
        }

        const month = invoice.due_date.substring(0, 7)

        if (!months[month]) {
            months[month] = {
                incoming: 0,
                outgoing: 0,
            }
        }

        const amount = Math.round(Number(invoice.amount_huf ?? 0))

        if (invoice.direction === 'incoming') {
            months[month].incoming += amount
        } else if (invoice.direction === 'outgoing') {
            months[month].outgoing += amount
        }

        return months
    }, {} as Record<string, { incoming: number; outgoing: number }>)

    const months = Object.keys(monthlyCashFlow).sort()

    const monthlyCashFlowData = months.length > 0
        ? (() => {
            const firstDate = new Date(`${months[0]}-01T00:00:00`)
            const lastDate = new Date(`${months[months.length - 1]}-01T00:00:00`)

            const result = []
            const currentDate = new Date(firstDate)

            while (currentDate <= lastDate) {
                const month = `${currentDate.getFullYear()}-${String(
                    currentDate.getMonth() + 1
                ).padStart(2, '0')}`

                result.push({
                    month,
                    incoming: monthlyCashFlow[month]?.incoming ?? 0,
                    outgoing: monthlyCashFlow[month]?.outgoing ?? 0,
                })

                currentDate.setMonth(currentDate.getMonth() + 1)
            }

            return result
        })()
        : []

    const partnerSummary = processedInvoices.reduce((partners, invoice) => {
        const partner = invoice.direction === 'incoming'
            ? invoice.seller
            : invoice.direction === 'outgoing'
                ? invoice.buyer
                : null

        if (!partner?.tax_number) {
            return partners
        }

        const taxNumber = partner.tax_number

        if (!partners[taxNumber]) {
            partners[taxNumber] = {
                name: partner.name,
                taxNumber,
                incoming: 0,
                outgoing: 0,
            }
        }

        const amount = Math.round(Number(invoice.amount_huf ?? 0))

        if (invoice.direction === 'incoming') {
            partners[taxNumber].incoming += amount
        } else if (invoice.direction === 'outgoing') {
            partners[taxNumber].outgoing += amount
        }

        return partners
    }, {} as Record<string, {
        name: string
        taxNumber: string
        incoming: number
        outgoing: number
    }>)

    const partnerSummaryData = Object.values(partnerSummary)

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

            <section className="cash-flow-section">
                <div className="section-heading">
                    <div>
                        <h2>Monthly Cash Flow</h2>
                        <p>Incoming and outgoing invoices by due date.</p>
                    </div>
                </div>

                <div className="cash-flow-chart">
                    <ResponsiveContainer width="100%" height={350}>
                        <LineChart data={monthlyCashFlowData}>
                            <CartesianGrid strokeDasharray="3 3" />
                            <XAxis dataKey="month" />
                            <YAxis
                                tickFormatter={(value) =>
                                    `${new Intl.NumberFormat('hu-HU', {
                                        notation: 'compact',
                                        maximumFractionDigits: 1,
                                    }).format(Number(value))} Ft`
                                }
                            />
                            <Tooltip
                                labelFormatter={(label) => `Month: ${label}`}
                                formatter={(value) =>
                                    `${Number(value).toLocaleString('hu-HU')} Ft`
                                }
                            />
                            <Legend />

                            <Line
                                type="monotone"
                                dataKey="incoming"
                                name="Incoming"
                                stroke="#16a34a"
                                strokeWidth={3}
                            />

                            <Line
                                type="monotone"
                                dataKey="outgoing"
                                name="Outgoing"
                                stroke="#dc2626"
                                strokeWidth={3}
                            />
                        </LineChart>
                    </ResponsiveContainer>
                </div>
            </section>

            <section className="invoice-section">
                <div className="section-heading">
                    <div>
                        <h2>Partner Summary</h2>
                        <p>Incoming and outgoing amounts by partner.</p>
                    </div>
                </div>

                <div className="table-container">
                    <table>
                        <thead>
                            <tr>
                                <th>Partner</th>
                                <th>Tax number</th>
                                <th>Incoming (HUF)</th>
                                <th>Outgoing (HUF)</th>
                            </tr>
                        </thead>

                        <tbody>
                            {partnerSummaryData.map((partner) => (
                                <tr key={partner.taxNumber}>
                                    <td>{partner.name}</td>
                                    <td>{partner.taxNumber}</td>
                                    <td>
                                        {partner.incoming.toLocaleString('hu-HU', {
                                            maximumFractionDigits: 0,
                                        })} Ft
                                    </td>
                                    <td>
                                        {partner.outgoing.toLocaleString('hu-HU', {
                                            maximumFractionDigits: 0,
                                        })} Ft
                                    </td>
                                </tr>
                            ))}

                            {partnerSummaryData.length === 0 && (
                                <tr>
                                    <td colSpan={4}>No partner data available.</td>
                                </tr>
                            )}
                        </tbody>
                    </table>
                </div>
            </section>

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
