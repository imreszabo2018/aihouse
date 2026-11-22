export type Invoice = {
    id: number
    invoice_number: string
    due_date: string | null
    gross_amount: number | string
    amount_huf: number | string | null
    currency: string
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

export type MonthlyCashFlow = {
    month: string
    incoming: number
    outgoing: number
}

export function calculateMonthlyCashFlow(
    invoices: Invoice[]
): MonthlyCashFlow[] {
    const monthlyTotals = new Map<string, MonthlyCashFlow>()

    const processedInvoices = invoices.filter(
        (invoice) => invoice.processing_status === 'processed'
    )

    for (const invoice of processedInvoices) {
        if (!invoice.due_date || !invoice.direction) {
            continue
        }

        const month = invoice.due_date.substring(0, 7)
        const amount = Math.round(Number(invoice.amount_huf ?? 0))

        if (!monthlyTotals.has(month)) {
            monthlyTotals.set(month, {
                month,
                incoming: 0,
                outgoing: 0,
            })
        }

        const monthlyData = monthlyTotals.get(month)!

        if (invoice.direction === 'incoming') {
            monthlyData.incoming += amount
        } else {
            monthlyData.outgoing += amount
        }
    }

    const months = Array.from(monthlyTotals.keys()).sort()

    if (months.length === 0) {
        return []
    }

    const firstMonth = new Date(`${months[0]}-01T00:00:00`)
    const lastMonth = new Date(`${months[months.length - 1]}-01T00:00:00`)

    const result: MonthlyCashFlow[] = []

    const currentMonth = new Date(firstMonth)

    while (currentMonth <= lastMonth) {
        const month = `${currentMonth.getFullYear()}-${String(
            currentMonth.getMonth() + 1
        ).padStart(2, '0')}`

        result.push(
            monthlyTotals.get(month) ?? {
                month,
                incoming: 0,
                outgoing: 0,
            }
        )

        currentMonth.setMonth(currentMonth.getMonth() + 1)
    }

    return result
}

export type PartnerSummary = {
    name: string
    taxNumber: string
    incoming: number
    outgoing: number
}

export function calculatePartnerSummary(
    invoices: Invoice[]
): PartnerSummary[] {
    const partners = new Map<string, PartnerSummary>()

    const processedInvoices = invoices.filter(
        (invoice) => invoice.processing_status === 'processed'
    )

    for (const invoice of processedInvoices) {
        if (!invoice.direction) {
            continue
        }

        const partner =
            invoice.direction === 'incoming'
                ? invoice.seller
                : invoice.buyer

        if (!partner?.tax_number) {
            continue
        }

        const taxNumber = partner.tax_number.trim()
        const amount = Math.round(Number(invoice.amount_huf ?? 0))

        if (!partners.has(taxNumber)) {
            partners.set(taxNumber, {
                name: partner.name,
                taxNumber,
                incoming: 0,
                outgoing: 0,
            })
        }

        const summary = partners.get(taxNumber)!

        if (invoice.direction === 'incoming') {
            summary.incoming += amount
        } else {
            summary.outgoing += amount
        }
    }

    return Array.from(partners.values())
}
