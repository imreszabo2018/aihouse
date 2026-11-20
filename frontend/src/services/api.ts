
const API_BASE_URL = 'http://localhost:8000/api'

export async function getInvoices() {
  const response = await fetch(`${API_BASE_URL}/invoices`)

  if (!response.ok) {
    throw new Error('Failed to fetch invoices')
  }

  return response.json()
}

export async function uploadInvoice(file: File) {
  const formData = new FormData()

  formData.append('file', file)

  const response = await fetch(`${API_BASE_URL}/invoices`, {
    method: 'POST',
    body: formData,
  })

  if (!response.ok) {
    throw new Error('Failed to upload invoice')
  }

  return response.json()
}