<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Monitoring Pengadaan Barang</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css" rel="stylesheet">
    <style>
        .table-header-navy th {
            background-color: #14213d !important;
            color: #fff !important;
            border-bottom: 2px solid #e5e7eb !important;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        .modern-table {
            border-collapse: separate;
            border-spacing: 0;
            background: #f8fafc;
            box-shadow: 0 2px 8px rgba(20,33,61,0.06);
            border-radius: 0.5rem;
            overflow: hidden;
        }
        .modern-table th, .modern-table td {
            border: 1px solid #e5e7eb !important;
            vertical-align: middle;
            text-align: center;
            width: calc(100% / 7);
            word-break: break-word;
        }
        .modern-table tbody tr:hover {
            background-color: #e9ecef !important;
            transition: background 0.2s;
        }
        .badge.bg-success {
            background-color: #22c55e !important;
        }
        .badge.bg-danger {
            background-color: #ef4444 !important;
        }
        .badge.bg-secondary {
            background-color: #6c757d !important;
        }
    </style>
</head>
<body>
<div class="container py-4">
    <h2 class="mb-4 text-center">Dashboard Monitoring Pengadaan Barang</h2>
    <div class="table-responsive">
        <table id="monitoringTable" class="table table-bordered table-hover align-middle modern-table text-center" style="table-layout: fixed; width: 100%;">
            <thead class="table-header-navy">
            <tr>
                <th>Project Code</th>
                <th>Project Name</th>
                <th>PR No</th>
                <th>PO No</th>
                <th>PR Date</th>
                <th>PR Status</th>
                <th>PO Payment</th>
            </tr>
            </thead>
            <tbody>
            <!-- Data will be loaded here by JS -->
            </tbody>
        </table>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="detailModal" tabindex="-1" aria-labelledby="detailModalLabel" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="detailModalLabel">Detail Pengadaan</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
                <!-- Konten akan diisi oleh JS -->
      </div>
    </div>
  </div>
</div>

<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script>
$(document).ready(function() {
    $.getJSON('data_monitoring.json', function(data) {
        let tbody = '';
        data.forEach(function(row, idx) {
            tbody += `<tr class="pointer" data-idx="${idx}">
                <td>${row.Project_Code || '-'}</td>
                <td>${row.Project_Name || '-'}</td>
                <td>${row.PR_No || '-'}</td>
                <td>${row.PO_No || '-'}</td>
                <td>${row.PR_Date || '-'}</td>
                <td>${row.PR_Status || '-'}</td>
                <td>${row.PO_Payment || '-'}</td>
            </tr>`;
        });
        $('#monitoringTable tbody').html(tbody);
        let table = $('#monitoringTable').DataTable({
            responsive: true,
            language: {
                search: "Cari:",
                lengthMenu: "Tampilkan _MENU_ entri",
                info: "Menampilkan _START_ sampai _END_ dari _TOTAL_ entri",
                paginate: {
                    first: "Pertama",
                    last: "Terakhir",
                    next: "Berikutnya",
                    previous: "Sebelumnya"
                }
            }
        });
        $('#monitoringTable tbody').on('click', 'tr', function() {
            let idx = $(this).data('idx');
            let detail = data[idx];
            // Mapping label sesuai urutan dan gambar
            const labelMap = {
                Project_Code: 'Project Code',
                Project_Name: 'Project Name',
                PR_No: 'Purchase Request No',
                PO_No: 'PO No',
                PR_Date: 'PR Date',
                Delivery_Date: 'Delivery Date',
                PO_Date: 'PO Date',
                Request_Delivery: 'Req Delivery',
                Item_Name: 'Item Name',
                Quantity: 'Total Purchase Order Qty',
                Invoicing_Status: 'Invoicing Status',
                Leadtime_PR_PO: 'ACT SPB TO REQUEST',
                Leadtime_PO_Deliv: 'STD LEADTIME PCH',
                ETA: 'ETA',
                Remark: 'ACT LEADTIME TO ETA',
                Act_Request_to_PO: 'SCORING SPB',
                Act_PR_to_Request: 'SCORING LEAD TIME',
                PO_Payment: 'PO Payment'
            };
            let fields = [];
            for (const key of Object.keys(labelMap)) {
                if (detail.hasOwnProperty(key)) {
                    fields.push({label: labelMap[key], value: detail[key] || '-'});
                }
            }
            let leftCol = '', rightCol = '';
            fields.forEach(function(field, i) {
                let html = `<div class=\"mb-2\"><strong>${field.label}:</strong> <span>${field.value}</span></div>`;
                if (i % 2 === 0) leftCol += html;
                else rightCol += html;
            });
            let gridHtml = `<div class=\"row\">
                <div class=\"col-md-6\">${leftCol}</div>
                <div class=\"col-md-6\">${rightCol}</div>
            </div>`;
            $('.modal-body').html(gridHtml);
            var modal = new bootstrap.Modal(document.getElementById('detailModal'));
            modal.show();
        });
    });
});
</script>
</body>
</html>
