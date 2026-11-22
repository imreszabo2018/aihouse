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
import { useNavigate } from 'react-router-dom'
import {
    calculateMonthlyCashFlow,
    calculatePartnerSummary,
    type Invoice,
} from '../utils/invoiceStatistics'

function Dashboard() {

    const [refreshKey, setRefreshKey] = useState(0)
    const [isUploading, setIsUploading] = useState(false)
    const [uploadMessage, setUploadMessage] = useState('')
    const [invoices, setInvoices] = useState<Invoice[]>([])
    const navigate = useNavigate()

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

    const monthlyCashFlowData = calculateMonthlyCashFlow(invoices)

    const partnerSummaryData = calculatePartnerSummary(invoices)

    async function handleUpload(files: File[]) {
        setIsUploading(true)
        setUploadMessage('')

        let successfulUploads = 0
        let failedUploads = 0

        for (const file of files) {
            try {
                await uploadInvoice(file)
                successfulUploads++
            } catch (error) {
                console.error(`Upload error for ${file.name}:`, error)
                failedUploads++
            }
        }

        setRefreshKey((current) => current + 1)

        if (failedUploads === 0) {
            setUploadMessage(
                `Successfully processed ${successfulUploads} invoice(s).`
            )
        } else {
            setUploadMessage(
                `Processed: ${successfulUploads}, failed: ${failedUploads}.`
            )
        }

        setIsUploading(false)
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
                            multiple
                            hidden
                            disabled={isUploading}
                            onChange={(event) => {
                                const files = Array.from(event.target.files ?? [])

                                if (files.length > 0) {
                                    handleUpload(files)
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
                                <th>Category</th>
                                {partnerSummaryData.map((partner) => (
                                    <th key={partner.taxNumber}>
                                        {partner.name}
                                        <br />
                                        <small>{partner.taxNumber}</small>
                                    </th>
                                ))}
                            </tr>
                        </thead>

                        <tbody>
                            <tr>
                                <td>Össz bejövő</td>
                                {partnerSummaryData.map((partner) => (
                                    <td key={partner.taxNumber}>
                                        {partner.incoming.toLocaleString('hu-HU')} Ft
                                    </td>
                                ))}
                            </tr>

                            <tr>
                                <td>Össz kimenő</td>
                                {partnerSummaryData.map((partner) => (
                                    <td key={partner.taxNumber}>
                                        {partner.outgoing.toLocaleString('hu-HU')} Ft
                                    </td>
                                ))}
                            </tr>

                            {partnerSummaryData.length === 0 && (
                                <tr>
                                    <td colSpan={1}>
                                        No partner data available.
                                    </td>
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

                    <button
                        className="secondary-button"
                        onClick={() => navigate('/invoices')}
                    >
                        View all
                    </button>
                </div>

                <InvoiceList refreshKey={refreshKey} />
            </section>
        </section>
    )
}

export default Dashboard
