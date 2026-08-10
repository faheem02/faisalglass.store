<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Faisal Glass - Receipt</title>
    <!-- html2canvas for PDF export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js">
    </script>
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        /* ─── Reset & Base ─── */
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #e6e9ef;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            font-size: 16px;
            font-family: 'Poppins', sans-serif;
            padding: 20px;
        }

        /* ─── Toolbar ─── */
        .toolbar {
            display: flex;
            gap: 16px;
            margin-bottom: 24px;
            flex-wrap: wrap;
            justify-content: center;
        }
        .toolbar button {
            padding: 10px 28px;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: 0.2s;
            background: #1a2a3a;
            color: #fff;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.15);
        }
        .toolbar button:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 18px rgba(0, 0, 0, 0.2);
        }
        .toolbar button.pdf-btn {
            background: #c0392b;
        }
        .toolbar button.print-btn {
            background: #2c6e9c;
        }

        /* ─── Receipt Container (A5) ─── */
        #receipt-wrapper {
            background: #ffffff;
            box-shadow: 0 12px 40px rgba(0, 0, 0, 0.18);
            border-radius: 4px;
            padding: 12px;
        }

        #receipt {
            width: 148mm;
            min-height: 210mm;
            background: #ffffff;
            padding: 10mm 8mm;
            font-size: 10.5px;
            line-height: 1.5;
            color: #1e1e1e;
            display: flex;
            flex-direction: column;
            position: relative;
            border: 1px solid #d0d4dc;
            border-radius: 2px;
        }

        /* ─── Typography ─── */
        .brand-name {
            font-size: 28px;
            font-weight: 800;
            letter-spacing: 1.5px;
            color: #0b1e2e;
            text-transform: uppercase;
            line-height: 1.1;
        }
        .brand-sub {
            font-size: 13px;
            font-weight: 600;
            color: #2c3e50;
            letter-spacing: 0.5px;
            margin-top: 1px;
        }
        .brand-contact {
            font-size: 10.5px;
            color: #34495e;
            margin-top: 2px;
        }
        .brand-address {
            font-size: 10px;
            color: #4a5a6a;
            margin-top: 1px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 700;
            color: #0b1e2e;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 2px solid #0b1e2e;
            padding-bottom: 3px;
            margin: 12px 0 8px 0;
        }
        .section-title:first-of-type {
            margin-top: 8px;
        }

        .field-label {
            font-weight: 600;
            color: #1e2f3f;
            display: inline-block;
            min-width: 120px;
        }
        .field-value {
            font-weight: 400;
            color: #1e1e1e;
        }
        .field-row {
            margin: 3px 0;
            display: flex;
            align-items: baseline;
            flex-wrap: wrap;
        }
        .field-row .field-label {
            min-width: 120px;
        }

        /* ─── Payment Method Checkboxes ─── */
        .method-group {
            display: flex;
            flex-wrap: wrap;
            gap: 6px 18px;
            margin-top: 2px;
            align-items: center;
        }
        .method-item {
            display: flex;
            align-items: center;
            gap: 4px;
            font-size: 10.5px;
            color: #1e2f3f;
        }
        .method-item input[type="checkbox"] {
            width: 14px;
            height: 14px;
            accent-color: #0b1e2e;
            cursor: default;
            pointer-events: none;
            margin: 0;
        }

        /* ─── Received By ─── */
        .received-by {
            margin-top: 10px;
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            padding-top: 6px;
            border-top: 2px solid #0b1e2e;
        }
        .received-left .company-name {
            font-size: 14px;
            font-weight: 700;
            color: #0b1e2e;
        }
        .received-left .company-sub {
            font-size: 10px;
            color: #2c3e50;
        }
        .signature-area {
            text-align: center;
            min-width: 100px;
        }
        .signature-area .sig-label {
            font-size: 10px;
            color: #4a5a6a;
            border-top: 1px solid #4a5a6a;
            padding-top: 2px;
            min-width: 100px;
        }
        .signature-area .sig-company {
            font-size: 10px;
            font-weight: 600;
            color: #0b1e2e;
            margin-top: 2px;
        }

        /* ─── Footer ─── */
        .footer {
            margin-top: auto;
            padding-top: 10px;
            text-align: center;
            border-top: 1px solid #d0d4dc;
        }
        .footer .thankyou {
            font-size: 13px;
            font-weight: 700;
            color: #0b1e2e;
            letter-spacing: 0.5px;
        }
        .footer .generated {
            font-size: 9px;
            color: #6a7a8a;
            margin-top: 2px;
            font-style: italic;
        }

        /* ─── Print Styles ─── */
        @media print {
            body {
                background: #fff;
                padding: 0;
                margin: 0;
                display: block;
            }
            .toolbar {
                display: none !important;
            }
            #receipt-wrapper {
                box-shadow: none;
                border-radius: 0;
                padding: 0;
                margin: 0;
            }
            #receipt {
                width: 148mm;
                min-height: 210mm;
                padding: 10mm 8mm;
                border: none;
                border-radius: 0;
                box-shadow: none;
                margin: 0 auto;
            }
            .method-item input[type="checkbox"] {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
            .brand-name,
            .section-title,
            .received-left .company-name,
            .footer .thankyou {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }

        /* ─── Screen Responsive ─── */
        @media screen and (max-width: 600px) {
            #receipt {
                width: 100%;
                min-height: auto;
                padding: 6mm 5mm;
                font-size: 10px;
            }
            .brand-name {
                font-size: 22px;
            }
            .field-row .field-label {
                min-width: 90px;
            }
            .method-group {
                gap: 4px 12px;
            }
            .toolbar button {
                padding: 8px 18px;
                font-size: 14px;
            }
        }
    </style>
</head>
<body>

    <!-- ─── Toolbar ─── -->
    <div class="toolbar">
        <button class="print-btn" onclick="window.print()">🖨️ Print</button>
        <button class="pdf-btn" onclick="exportPDF()">📄 Download PDF</button>
        <button class="pdf-btn" style="background:#27ae60;" onclick="updateSampleData()">📥 Load Sample</button>
    </div>

    <!-- ─── Receipt ─── -->
    <div id="receipt-wrapper">
        <div id="receipt">
            <!-- ===== HEADER ===== -->
            <div>
                <div class="brand-name" id="brandName">FAISAL GLASS</div>
                <div class="brand-sub" id="brandSub">DEALERS OF GHANI GLASS LIMITED</div>
                <div class="brand-contact" id="brandContact">📞 0321-4186775</div>
                <div class="brand-address" id="brandAddress">📍 Lajna Chowk Collage Road Township Lahore</div>
            </div>

            <!-- ===== RECEIVED FROM ===== -->
            <div class="section-title">RECEIVED FROM</div>
            <div class="field-row">
                <span class="field-label">Customer Name</span>
                <span class="field-value" id="customerName"><strong></strong></span>
            </div>

            <!-- ===== PAYMENT DETAILS ===== -->
            <div class="section-title">PAYMENT DETAILS</div>

            <div class="field-row">
                <span class="field-label">Amount Received</span>
                <span class="field-value" id="amountDisplay" style="font-size:15px; font-weight:700; color:#0b1e2e;">Rs. 25,000.00</span>
            </div>

            <div class="field-row" style="align-items:flex-start;">
                <span class="field-label">Payment Method</span>
                <div class="method-group" id="methodGroup">
                    <!-- checkboxes rendered by JS -->
                </div>
            </div>

            <div class="field-row">
                <span class="field-label">Invoice / Order No.</span>
                <span class="field-value" id="invoiceNumber"><strong>INV-0025</strong></span>
            </div>

            <div class="field-row">
                <span class="field-label">Purpose of Payment</span>
                <span class="field-value" id="purposeText">Payment against Invoice INV-0025 (Supply of Glass)</span>
            </div>

            <div class="field-row">
                <span class="field-label">Remarks (Optional)</span>
                <span class="field-value" id="remarksText" style="color:#3a4a5a;">Thank you for your payment.</span>
            </div>

            <!-- ===== RECEIVED BY ===== -->
            <div class="received-by">
                <div class="received-left">
                    <div class="company-name">FAISAL GLASS</div>
                    <div class="company-sub">DEALERS OF GHANI GLASS LIMITED</div>
                </div>
                <div class="signature-area">
                    <div class="sig-label">Authorized Signature</div>
                    <div class="sig-company">GHANI GLASS LIMITED</div>
                </div>
            </div>

            <!-- ===== FOOTER ===== -->
            <div class="footer">
                <div class="thankyou">Thank You for Your Business!</div>
                <div class="generated">This is a computer generated receipt and does not require a physical signature.</div>
            </div>
        </div><!-- /receipt -->
    </div><!-- /receipt-wrapper -->

    <!-- ─── JavaScript ─── -->
    <script>
        // ================================================================
        //  🔷  DATA — Update this object from your software
        // ================================================================
        const receiptData = {
            // ── Company Info ──
            company: {
                name: 'FAISAL GLASS',
                sub: 'DEALERS OF GHANI GLASS LIMITED',
                phone: '0321-4186775',
                address: 'Lajna Chowk Collage Road Township Lahore',
            },

            // ── Receipt Fields ──
            customerName: '', // e.g. 'Ali Raza'
            amount: '25,000.00',
            paymentMethod: {
                cash: true, // checked
                bankTransfer: false,
                jazzCash: false,
                cheque: false,
                other: false,
            },
            invoiceNo: 'INV-0025',
            purpose: 'Payment against Invoice INV-0025 (Supply of Glass)',
            remarks: 'Thank you for your payment.',
        };

        // ================================================================
        //  🔷  RENDER — Populate the receipt from `receiptData`
        // ================================================================
        function renderReceipt() {
            const d = receiptData;

            // ── Header ──
            document.getElementById('brandName').textContent = d.company.name;
            document.getElementById('brandSub').textContent = d.company.sub;
            document.getElementById('brandContact').textContent = '📞 ' + d.company.phone;
            document.getElementById('brandAddress').textContent = '📍 ' + d.company.address;

            // ── Customer ──
            document.getElementById('customerName').innerHTML = d.customerName ? '<strong>' + d.customerName +
                '</strong>' : '<span style="color:#999;">—</span>';

            // ── Amount ──
            document.getElementById('amountDisplay').textContent = 'Rs. ' + d.amount;

            // ── Payment Method (checkboxes) ──
            const methods = [
                { key: 'cash', label: 'Cash' },
                { key: 'bankTransfer', label: 'Bank Transfer' },
                { key: 'jazzCash', label: 'JazzCash / Easypaisa' },
                { key: 'cheque', label: 'Cheque' },
                { key: 'other', label: 'Other' },
            ];
            const container = document.getElementById('methodGroup');
            container.innerHTML = '';
            methods.forEach(m => {
                const checked = d.paymentMethod[m.key] ? 'checked' : '';
                const label = document.createElement('label');
                label.className = 'method-item';
                label.innerHTML = `<input type="checkbox" ${checked} disabled /> ${m.label}`;
                container.appendChild(label);
            });

            // ── Invoice ──
            document.getElementById('invoiceNumber').innerHTML = '<strong>' + d.invoiceNo + '</strong>';

            // ── Purpose ──
            document.getElementById('purposeText').textContent = d.purpose;

            // ── Remarks ──
            document.getElementById('remarksText').textContent = d.remarks || '—';
        }

        // ─── Helper: update data and re-render ───
        function updateReceipt(newData) {
            Object.assign(receiptData, newData);
            renderReceipt();
        }

        // ─── Load sample data (for demo) ───
        function updateSampleData() {
            updateReceipt({
                customerName: 'Ali Raza',
                amount: '25,000.00',
                paymentMethod: {
                    cash: true,
                    bankTransfer: false,
                    jazzCash: false,
                    cheque: false,
                    other: false,
                },
                invoiceNo: 'INV-0025',
                purpose: 'Payment against Invoice INV-0025 (Supply of Glass)',
                remarks: 'Thank you for your payment.',
            });
        }

        // ================================================================
        //  🔷  EXPORT PDF (html2canvas + jsPDF)
        // ================================================================
        function exportPDF() {
            const receipt = document.getElementById('receipt');
            const btn = document.querySelector('.pdf-btn');
            const originalText = btn.textContent;
            btn.textContent = '⏳ Generating…';
            btn.disabled = true;

            if (typeof window.jspdf === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                script.onload = function() { doExport(); };
                script.onerror = function() {
                    alert('Failed to load PDF library. Check internet.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                };
                document.head.appendChild(script);
            } else {
                doExport();
            }

            function doExport() {
                const { jsPDF } = window.jspdf;
                const scale = 2.5;
                const rect = receipt.getBoundingClientRect();
                const width = rect.width;
                const height = rect.height;

                html2canvas(receipt, {
                    scale: scale,
                    useCORS: true,
                    allowTaint: false,
                    backgroundColor: '#ffffff',
                    logging: false,
                    width: width,
                    height: height,
                }).then((canvas) => {
                    const imgData = canvas.toDataURL('image/png');
                    const pdfWidth = 148;
                    const pdfHeight = 210;
                    const pdf = new jsPDF({ orientation: 'portrait', unit: 'mm', format: 'a5' });

                    const canvasAspect = canvas.width / canvas.height;
                    const pdfAspect = pdfWidth / pdfHeight;
                    let finalWidth = pdfWidth;
                    let finalHeight = pdfHeight;
                    if (canvasAspect > pdfAspect) {
                        finalHeight = pdfWidth / canvasAspect;
                    } else {
                        finalWidth = pdfHeight * canvasAspect;
                    }
                    const xOffset = (pdfWidth - finalWidth) / 2;
                    const yOffset = (pdfHeight - finalHeight) / 2;

                    pdf.addImage(imgData, 'PNG', xOffset, yOffset, finalWidth, finalHeight);
                    pdf.save('Faisal_Glass_Receipt_' + (receiptData.invoiceNo || 'INV') + '.pdf');

                    btn.textContent = originalText;
                    btn.disabled = false;
                }).catch((err) => {
                    console.error('html2canvas error:', err);
                    alert('Could not generate PDF. Please try again or use Print.');
                    btn.textContent = originalText;
                    btn.disabled = false;
                });
            }
        }

        // ─── Preload jsPDF ───
        (function preloadJSPDF() {
            if (typeof window.jspdf === 'undefined') {
                const script = document.createElement('script');
                script.src = 'https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js';
                script.async = true;
                document.head.appendChild(script);
            }
        })();

        // ─── Initial render ───
        renderReceipt();
        console.log('✅ Receipt ready. Update `receiptData` object to change content.');
    </script>

</body>
</html>