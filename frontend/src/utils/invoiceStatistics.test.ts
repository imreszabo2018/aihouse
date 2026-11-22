import { describe, it, expect } from 'vitest'
import {
    calculateMonthlyCashFlow,
    calculatePartnerSummary,
    type Invoice,
} from './invoiceStatistics'

const createInvoice = (
    overrides: Partial<Invoice> = {}
): Invoice => ({
    id: 1,
    invoice_number: 'INV-001',
    due_date: '2026-01-15',
    gross_amount: 10000,
    amount_huf: 10000,
    currency: 'HUF',
    direction: 'incoming',
    processing_status: 'processed',
    seller: {
        name: 'Minta Kft.',
        tax_number: '12345678-2-42',
    },
    buyer: {
        name: 'Nordwind Solutions Kft.',
        tax_number: '26481937-2-43',
    },
    ...overrides,
})

describe('calculateMonthlyCashFlow', () => {
    it('calculates incoming and outgoing totals by due month', () => {
        const invoices = [
            createInvoice({ id: 1, amount_huf: 10000 }),
            createInvoice({
                id: 2,
                amount_huf: 20000,
                direction: 'outgoing',
            }),
            createInvoice({
                id: 3,
                amount_huf: 15000,
                due_date: '2026-02-10',
            }),
        ]

        expect(calculateMonthlyCashFlow(invoices)).toEqual([
            { month: '2026-01', incoming: 10000, outgoing: 20000 },
            { month: '2026-02', incoming: 15000, outgoing: 0 },
        ])
    })

    it('fills missing months with zero values', () => {
        const invoices = [
            createInvoice({ due_date: '2026-01-15' }),
            createInvoice({
                id: 2,
                due_date: '2026-03-15',
                amount_huf: 30000,
            }),
        ]

        expect(calculateMonthlyCashFlow(invoices)).toEqual([
            { month: '2026-01', incoming: 10000, outgoing: 0 },
            { month: '2026-02', incoming: 0, outgoing: 0 },
            { month: '2026-03', incoming: 30000, outgoing: 0 },
        ])
    })

    it('ignores invoices that are not processed', () => {
        const invoices = [
            createInvoice({ amount_huf: 10000 }),
            createInvoice({
                id: 2,
                amount_huf: 50000,
                processing_status: 'failed',
            }),
        ]

        expect(calculateMonthlyCashFlow(invoices)).toEqual([
            { month: '2026-01', incoming: 10000, outgoing: 0 },
        ])
    })

    it('rounds HUF amounts to whole forints', () => {
        const invoices = [
            createInvoice({ amount_huf: 10000.6 }),
            createInvoice({
                id: 2,
                amount_huf: 10000.4,
            }),
        ]

        expect(calculateMonthlyCashFlow(invoices)).toEqual([
            { month: '2026-01', incoming: 20001, outgoing: 0 },
        ])
    })

    it('returns an empty array when there are no valid invoices', () => {
        expect(
            calculateMonthlyCashFlow([
                createInvoice({ due_date: null }),
            ])
        ).toEqual([])
    })
})

describe('calculatePartnerSummary', () => {
    it('groups invoices by partner tax number', () => {
        const invoices = [
            createInvoice({
                id: 1,
                amount_huf: 10000,
                seller: {
                    name: 'Minta Kft.',
                    tax_number: '12345678-2-42',
                },
            }),
            createInvoice({
                id: 2,
                amount_huf: 20000,
                seller: {
                    name: 'MINTA KFT.',
                    tax_number: '12345678-2-42',
                },
            }),
        ]

        expect(calculatePartnerSummary(invoices)).toEqual([
            {
                name: 'Minta Kft.',
                taxNumber: '12345678-2-42',
                incoming: 30000,
                outgoing: 0,
            },
        ])
    })

    it('separates incoming and outgoing amounts', () => {
        const invoices = [
            createInvoice({
                amount_huf: 10000,
                direction: 'incoming',
            }),
            createInvoice({
                id: 2,
                amount_huf: 25000,
                direction: 'outgoing',
                buyer: {
                    name: 'Másik Kft.',
                    tax_number: '87654321-2-42',
                },
            }),
        ]

        expect(calculatePartnerSummary(invoices)).toEqual([
            {
                name: 'Minta Kft.',
                taxNumber: '12345678-2-42',
                incoming: 10000,
                outgoing: 0,
            },
            {
                name: 'Másik Kft.',
                taxNumber: '87654321-2-42',
                incoming: 0,
                outgoing: 25000,
            },
        ])
    })

    it('ignores invoices that are not processed', () => {
        const invoices = [
            createInvoice({
                amount_huf: 10000,
                processing_status: 'failed',
            }),
        ]

        expect(calculatePartnerSummary(invoices)).toEqual([])
    })

    it('ignores invoices without partner tax numbers', () => {
        const invoices = [
            createInvoice({
                seller: {
                    name: 'Ismeretlen Kft.',
                    tax_number: null,
                },
            }),
        ]

        expect(calculatePartnerSummary(invoices)).toEqual([])
    })
})