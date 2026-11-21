import { useState } from 'react'
import InvoiceList from '../components/InvoiceList'
import { uploadInvoice } from '../services/api'

function Invoices() {
    const [refreshKey, setRefreshKey] = useState(0)
    const [isUploading, setIsUploading] = useState(false)
    const [uploadMessage, setUploadMessage] = useState('')
    const [uploadProgress, setUploadProgress] = useState('')
    const [uploadStatus, setUploadStatus] = useState<'success' | 'error' | ''>('')

    async function handleUpload(files: File[]) {
        if (files.length === 0) return

        setIsUploading(true)
        setUploadMessage('')
        setUploadStatus('')

        let successful = 0
        let failed = 0

        for (let i = 0; i < files.length; i++) {
            const file = files[i]

            setUploadProgress(
                `Processing ${i + 1} of ${files.length}: ${file.name}`
            )

            try {
                await uploadInvoice(file)
                successful++
            } catch (error) {
                console.error(`Failed to upload ${file.name}:`, error)
                failed++
            }
        }

        setRefreshKey((current) => current + 1)

        setUploadMessage(
            `Finished! Successful: ${successful}, Failed: ${failed}`
        )
        setUploadStatus(failed === 0 ? 'success' : 'error')
        setUploadProgress('')
        setIsUploading(false)
    }

    return (
        <div className="page-content right-content">
            <div className="page-heading">
                <div>
                    <h1>Invoices</h1>
                    <p>Manage and review your invoices.</p>
                </div>

                <div className="upload-container">
                    <label className={`primary-button ${isUploading ? 'uploading' : ''}`}>
                        {isUploading ? 'Processing...' : '+ Upload invoices'}

                        <input
                            type="file"
                            accept="application/pdf"
                            multiple
                            hidden
                            disabled={isUploading}
                            onChange={(event) => {
                                const files = Array.from(
                                    event.target.files ?? []
                                )

                                handleUpload(files)
                                event.target.value = ''
                            }}
                        />
                    </label>

                    {uploadProgress && (
                        <p className="upload-message">
                            {uploadProgress}
                        </p>
                    )}

                    {uploadMessage && (
                        <p className={`upload-message upload-${uploadStatus}`}>
                            {uploadMessage}
                        </p>
                    )}
                </div>
            </div>

            <InvoiceList refreshKey={refreshKey} />
        </div>
    )
}

export default Invoices
