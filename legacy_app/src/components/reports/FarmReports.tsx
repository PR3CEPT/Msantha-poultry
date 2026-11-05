import React, { useState } from 'react';
import { useFarmData } from '../../hooks/useFarmData';
import { formatDate, formatMWK } from '../../utils/i18n';
import { exportMonthlyFarmExcel } from '../../services/excelExport';
import { FileText, Download, Printer, Filter, Calendar, FileSpreadsheet, Check } from 'lucide-react';

type ReportType =
  | 'egg_production'
  | 'flock_inventory'
  | 'customer_incubation'
  | 'feed_consumption'
  | 'sales_summary'
  | 'cashbook_pnl'
  | 'mortality_analysis';

export const FarmReports: React.FC = () => {
  const { db, settings } = useFarmData();

  const [selectedReport, setSelectedReport] = useState<ReportType>('egg_production');
  const [selectedMonth, setSelectedMonth] = useState(
    new Date().toISOString().substring(0, 7)
  );
  const [excelSuccess, setExcelSuccess] = useState(false);
  const [startDate, setStartDate] = useState('');
  const [endDate, setEndDate] = useState('');

  const handlePrint = () => {
    window.print();
  };

  const exportCSV = (filename: string, headers: string[], rows: (string | number)[][]) => {
    const csvContent =
      'data:text/csv;charset=utf-8,' +
      [headers.join(','), ...rows.map((e) => e.map((cell) => `"${String(cell).replace(/"/g, '""')}"`).join(','))].join('\n');
    const encodedUri = encodeURI(csvContent);
    const link = document.createElement('a');
    link.setAttribute('href', encodedUri);
    link.setAttribute('download', `${filename}_${new Date().toISOString().split('T')[0]}.csv`);
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
  };

  const handleExportCurrent = () => {
    if (selectedReport === 'egg_production') {
      const headers = ['Date', 'Flock', 'Species', 'Total Collected', 'Saleable', 'Cracked', 'Dirty', 'Damaged', 'Farm Used', 'Incubation Reserved', 'Recorded By'];
      const rows = db.eggCollections.map((e) => [
        e.collectionDate,
        e.flockName,
        e.species,
        e.totalCollected,
        e.saleableCount,
        e.crackedCount,
        e.dirtyCount,
        e.damagedRejectedCount,
        e.farmUsedCount || 0,
        e.reservedIncubationCount || 0,
        e.recordedBy,
      ]);
      exportCSV('msantha_egg_production', headers, rows);
    } else if (selectedReport === 'customer_incubation') {
      const headers = ['Batch Code', 'Customer', 'Phone', 'Village', 'Species', 'Date Set', 'Eggs Accepted', 'Expected Hatch', 'Total Charge (MK)', 'Paid (MK)', 'Balance Due (MK)', 'Status'];
      const rows = db.customerIncubations.map((b) => [
        b.batchCode,
        b.customerName,
        b.customerPhone,
        b.customerVillage || '',
        b.species,
        b.dateSet,
        b.eggsAccepted,
        b.expectedHatchDate,
        b.totalCharge,
        b.amountPaid,
        b.balanceDue,
        b.status,
      ]);
      exportCSV('msantha_customer_incubation', headers, rows);
    } else if (selectedReport === 'flock_inventory') {
      const headers = ['Code', 'Name', 'Species', 'Breed', 'Current Count', 'Initial Count', 'Shed', 'Purpose', 'Age Group', 'Acquired Date'];
      const rows = db.flocks.map((f) => [
        f.flockCode,
        f.name,
        f.species,
        f.breed,
        f.currentQuantity,
        f.initialQuantity,
        f.housingShed,
        f.purpose,
        f.ageGroup,
        f.dateAcquired,
      ]);
      exportCSV('msantha_flock_inventory', headers, rows);
    } else if (selectedReport === 'sales_summary') {
      const headers = ['Invoice', 'Date', 'Customer', 'Product', 'Quantity', 'Unit', 'Total (MK)', 'Paid (MK)', 'Balance (MK)', 'Method', 'Salesperson'];
      const rows = db.sales.map((s) => [
        s.invoiceNumber,
        s.date,
        s.customerName,
        s.description,
        s.quantity,
        s.unit,
        s.total,
        s.paymentReceived,
        s.balanceDue,
        s.paymentMethod,
        s.salesperson,
      ]);
      exportCSV('msantha_sales_summary', headers, rows);
    } else if (selectedReport === 'cashbook_pnl') {
      const headers = ['Date', 'Type', 'Category', 'Description', 'Amount (MK)', 'Payment Method', 'Recorded By'];
      const rows = [
        ...db.sales.map((s) => [s.date, 'Income', s.productCategory, s.description, s.paymentReceived, s.paymentMethod, s.salesperson]),
        ...db.expenses.map((e) => [e.date, 'Expense', e.category, e.description, e.amount, e.paymentMethod, e.recordedBy]),
      ];
      exportCSV('msantha_cashbook', headers, rows);
    } else if (selectedReport === 'feed_consumption') {
      const headers = ['Date', 'Flock', 'Feed Item', 'Quantity (kg)', 'Recorded By'];
      const rows = db.feedUsage.map((u) => [u.date, u.flockName, u.productName, u.quantityUsedKg, u.recordedBy]);
      exportCSV('msantha_feed_consumption', headers, rows);
    } else if (selectedReport === 'mortality_analysis') {
      const headers = ['Date', 'Flock', 'Birds Lost', 'Suspected Cause', 'Observed Symptoms', 'Action Taken', 'Recorded By'];
      const rows = db.mortalityRecords.map((m) => [m.date, m.flockName, m.numberOfBirds, m.suspectedCause, m.observedSymptoms || '', m.actionTaken, m.recordedBy]);
      exportCSV('msantha_mortality_log', headers, rows);
    }
  };

  return (
    <div className="space-y-5 pb-20 lg:pb-10">
      {/* Title */}
      <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print">
        <div>
          <h2 className="text-xl font-bold tracking-tight text-slate-900">Farm Management Reports</h2>
          <p className="text-xs text-slate-500">
            Official performance reports for Isaac Chipeta & Sikizwayo Chipeta • CSV data export & printable audits.
          </p>
        </div>

        <div className="flex items-center gap-2">
          <button
            onClick={handleExportCurrent}
            className="flex items-center gap-1.5 px-3 py-2 border border-slate-300 bg-white hover:bg-slate-50 text-slate-700 font-semibold text-xs rounded-xl shadow-xs transition"
          >
            <Download className="w-4 h-4 text-slate-500" />
            <span>Download CSV</span>
          </button>
          <button
            onClick={handlePrint}
            className="flex items-center gap-1.5 px-3.5 py-2 bg-emerald-800 hover:bg-emerald-700 text-white font-semibold text-xs rounded-xl shadow-xs transition"
          >
            <Printer className="w-4 h-4" />
            <span>Print Report</span>
          </button>
        </div>
      </div>

      {/* Monthly Excel Spreadsheet Generator Card */}
      <div className="bg-gradient-to-r from-emerald-950 via-emerald-900 to-slate-900 text-white rounded-2xl p-4 sm:p-5 shadow-lg border border-emerald-800 no-print space-y-3">
        <div className="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div className="flex items-start gap-3">
            <div className="p-2.5 rounded-xl bg-emerald-800/80 text-emerald-300 border border-emerald-700 shrink-0">
              <FileSpreadsheet className="w-6 h-6" />
            </div>
            <div>
              <div className="flex items-center gap-2">
                <h3 className="text-sm sm:text-base font-bold text-white tracking-tight">
                  Monthly Excel Spreadsheet Report (.xlsx)
                </h3>
                <span className="text-[10px] font-black uppercase bg-emerald-700 text-emerald-100 px-2 py-0.5 rounded-full">
                  Multi-Sheet Workbook
                </span>
              </div>
              <p className="text-xs text-emerald-200/90 mt-1">
                Generates a complete Microsoft Excel (.xlsx) workbook for <strong>Msantha Poultry Farm</strong> with dedicated tabs:
              </p>
              <div className="flex flex-wrap gap-x-3 gap-y-1 mt-2 text-[11px] text-emerald-300">
                <span className="flex items-center gap-1">
                  <span className="w-1.5 h-1.5 rounded-full bg-amber-400" />
                  <strong>Sheet 1:</strong> Daily Egg Production & Grading
                </span>
                <span className="flex items-center gap-1">
                  <span className="w-1.5 h-1.5 rounded-full bg-amber-400" />
                  <strong>Sheet 2:</strong> Incubation & Hatch Rates
                </span>
                <span className="flex items-center gap-1">
                  <span className="w-1.5 h-1.5 rounded-full bg-amber-400" />
                  <strong>Sheet 3:</strong> Livestock & Flock Stocks
                </span>
                <span className="flex items-center gap-1">
                  <span className="w-1.5 h-1.5 rounded-full bg-amber-400" />
                  <strong>Sheet 4:</strong> Monthly Executive Summary
                </span>
              </div>
            </div>
          </div>

          {/* Month Selector & Download Button */}
          <div className="flex flex-col sm:flex-row sm:items-center gap-2 bg-emerald-900/60 p-2.5 rounded-xl border border-emerald-800/80 shrink-0">
            <div>
              <label className="block text-[10px] uppercase font-bold text-emerald-300 mb-1">
                Select Month & Year:
              </label>
              <input
                type="month"
                value={selectedMonth}
                onChange={(e) => setSelectedMonth(e.target.value)}
                className="bg-white text-slate-900 font-bold px-2.5 py-1.5 rounded-lg text-xs border border-emerald-600 focus:outline-hidden"
              />
            </div>
            <button
              onClick={() => {
                exportMonthlyFarmExcel(db, selectedMonth);
                setExcelSuccess(true);
                setTimeout(() => setExcelSuccess(false), 3000);
              }}
              className="mt-2 sm:mt-4 px-4 py-2 bg-amber-500 hover:bg-amber-400 text-slate-950 font-bold text-xs rounded-xl flex items-center justify-center gap-2 shadow-md transition active:scale-95"
            >
              {excelSuccess ? <Check className="w-4 h-4 text-emerald-950" /> : <Download className="w-4 h-4" />}
              <span>{excelSuccess ? 'Downloaded!' : 'Export Excel (.xlsx)'}</span>
            </button>
          </div>
        </div>
      </div>

      {/* Report Selector Pills */}
      <div className="bg-white p-3 rounded-xl border border-slate-200 no-print">
        <label className="block text-xs font-bold text-slate-700 mb-2">Select Report Category</label>
        <div className="flex flex-wrap gap-1.5">
          {[
            { id: 'egg_production', label: 'Egg Production & Grading' },
            { id: 'customer_incubation', label: 'Customer Incubation Ledger' },
            { id: 'flock_inventory', label: 'Flock Inventory & Bird Counts' },
            { id: 'sales_summary', label: 'Sales & Customer Revenue' },
            { id: 'cashbook_pnl', label: 'Cashbook & Net Cashflow' },
            { id: 'feed_consumption', label: 'Feed Usage & Inventory' },
            { id: 'mortality_analysis', label: 'Mortality & Losses Analysis' },
          ].map((rep) => (
            <button
              key={rep.id}
              onClick={() => setSelectedReport(rep.id as ReportType)}
              className={`px-3 py-1.5 rounded-lg text-xs font-semibold transition ${
                selectedReport === rep.id
                  ? 'bg-emerald-800 text-white'
                  : 'bg-slate-100 text-slate-700 hover:bg-slate-200'
              }`}
            >
              {rep.label}
            </button>
          ))}
        </div>
      </div>

      {/* Formatted Report Card (Ready for Print or Viewing) */}
      <div className="bg-white rounded-xl border border-slate-200 p-6 shadow-sm printable-report" id="printable-report">
        {/* Farm Header */}
        <div className="pb-4 border-b border-slate-200 flex flex-col sm:flex-row sm:items-center justify-between gap-3">
          <div className="flex items-center gap-3">
            <div className="w-12 h-12 rounded-xl bg-emerald-900 p-2 shrink-0">
              <img src="/icon.svg" alt="Msantha Farm" className="w-full h-full object-contain" />
            </div>
            <div>
              <h2 className="text-lg font-black text-slate-900 uppercase tracking-tight">
                {settings.farmName}
              </h2>
              <p className="text-xs text-slate-600 font-semibold">{settings.location}</p>
              <p className="text-[11px] text-slate-500">
                Managing Director: {settings.managingDirector} • Marketing: {settings.marketingManager}
              </p>
            </div>
          </div>

          <div className="text-left sm:text-right text-xs text-slate-500">
            <p className="font-bold text-slate-800 uppercase tracking-wider text-[10px]">Official Farm Report</p>
            <p>Generated: {new Date().toLocaleDateString('en-GB')}</p>
            <p className="text-[11px] text-emerald-800 font-medium">Currency: Malawi Kwacha (MWK)</p>
          </div>
        </div>

        {/* Report Content Table */}
        <div className="mt-4">
          {/* 1. Egg Production Report */}
          {selectedReport === 'egg_production' && (
            <div className="space-y-3">
              <h3 className="text-sm font-bold text-slate-900">Daily Egg Production & Quality Breakdown</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold">
                    <tr>
                      <th className="p-2">Date</th>
                      <th className="p-2">Flock</th>
                      <th className="p-2 text-right">Collected</th>
                      <th className="p-2 text-right">Trays</th>
                      <th className="p-2 text-right text-emerald-800">Saleable</th>
                      <th className="p-2 text-right">Cracked</th>
                      <th className="p-2 text-right">Dirty</th>
                      <th className="p-2 text-right">Damaged</th>
                      <th className="p-2 text-right">Farm Used</th>
                      <th className="p-2 text-right">Incubation</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {db.eggCollections.map((e) => (
                      <tr key={e.id}>
                        <td className="p-2 font-medium">{formatDate(e.collectionDate)}</td>
                        <td className="p-2">{e.flockName}</td>
                        <td className="p-2 text-right font-black">{e.totalCollected}</td>
                        <td className="p-2 text-right">{(e.totalCollected / 30).toFixed(1)}</td>
                        <td className="p-2 text-right font-bold text-emerald-800">{e.saleableCount}</td>
                        <td className="p-2 text-right text-amber-800">{e.crackedCount}</td>
                        <td className="p-2 text-right">{e.dirtyCount}</td>
                        <td className="p-2 text-right text-rose-700">{e.damagedRejectedCount}</td>
                        <td className="p-2 text-right">{e.farmUsedCount || 0}</td>
                        <td className="p-2 text-right font-semibold text-cyan-800">{e.reservedIncubationCount || 0}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 2. Customer Incubation Report */}
          {selectedReport === 'customer_incubation' && (
            <div className="space-y-3">
              <h3 className="text-sm font-bold text-slate-900">Commercial Customer Incubation Ledger</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold">
                    <tr>
                      <th className="p-2">Batch</th>
                      <th className="p-2">Customer</th>
                      <th className="p-2">Species</th>
                      <th className="p-2">Setting Date</th>
                      <th className="p-2 text-right">Accepted</th>
                      <th className="p-2 text-right">Total Fee</th>
                      <th className="p-2 text-right text-emerald-800">Paid</th>
                      <th className="p-2 text-right text-amber-800">Balance</th>
                      <th className="p-2">Status</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {db.customerIncubations.map((b) => (
                      <tr key={b.id}>
                        <td className="p-2 font-mono font-bold">{b.batchCode}</td>
                        <td className="p-2 font-semibold">
                          {b.customerName}
                          <div className="text-[10px] text-slate-400">{b.customerPhone}</div>
                        </td>
                        <td className="p-2">{b.species}</td>
                        <td className="p-2">{formatDate(b.dateSet)}</td>
                        <td className="p-2 text-right font-black">{b.eggsAccepted}</td>
                        <td className="p-2 text-right font-bold">{formatMWK(b.totalCharge)}</td>
                        <td className="p-2 text-right font-bold text-emerald-800">{formatMWK(b.amountPaid)}</td>
                        <td className="p-2 text-right font-bold text-amber-800">{formatMWK(b.balanceDue)}</td>
                        <td className="p-2 capitalize">{b.status.replace('_', ' ')}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 3. Flock Inventory Report */}
          {selectedReport === 'flock_inventory' && (
            <div className="space-y-3">
              <h3 className="text-sm font-bold text-slate-900">Current Flock Inventory & Housing Allocation</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold">
                    <tr>
                      <th className="p-2">Flock Code</th>
                      <th className="p-2">Flock Name</th>
                      <th className="p-2">Species</th>
                      <th className="p-2">Breed</th>
                      <th className="p-2 text-right">Current Birds</th>
                      <th className="p-2 text-right">Initial Intake</th>
                      <th className="p-2">Housing / Shed</th>
                      <th className="p-2">Purpose</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {db.flocks.map((f) => (
                      <tr key={f.id}>
                        <td className="p-2 font-mono font-bold">{f.flockCode}</td>
                        <td className="p-2 font-bold">{f.name}</td>
                        <td className="p-2 font-semibold text-emerald-900">{f.species}</td>
                        <td className="p-2">{f.breed}</td>
                        <td className="p-2 text-right font-black text-slate-900">{f.currentQuantity}</td>
                        <td className="p-2 text-right text-slate-500">{f.initialQuantity}</td>
                        <td className="p-2">{f.housingShed}</td>
                        <td className="p-2 capitalize">{f.purpose}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 4. Sales Summary Report */}
          {selectedReport === 'sales_summary' && (
            <div className="space-y-3">
              <h3 className="text-sm font-bold text-slate-900">Sales Register & Product Breakdown</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold">
                    <tr>
                      <th className="p-2">Invoice #</th>
                      <th className="p-2">Date</th>
                      <th className="p-2">Customer</th>
                      <th className="p-2">Product Description</th>
                      <th className="p-2 text-right">Quantity</th>
                      <th className="p-2 text-right">Total (MK)</th>
                      <th className="p-2 text-right text-emerald-800">Paid</th>
                      <th className="p-2 text-right text-amber-800">Balance</th>
                      <th className="p-2">Salesperson</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {db.sales.map((s) => (
                      <tr key={s.id}>
                        <td className="p-2 font-mono font-bold">{s.invoiceNumber}</td>
                        <td className="p-2">{formatDate(s.date)}</td>
                        <td className="p-2 font-semibold">{s.customerName}</td>
                        <td className="p-2">{s.description}</td>
                        <td className="p-2 text-right font-medium">{s.quantity} {s.unit}</td>
                        <td className="p-2 text-right font-black">{formatMWK(s.total)}</td>
                        <td className="p-2 text-right font-bold text-emerald-800">{formatMWK(s.paymentReceived)}</td>
                        <td className="p-2 text-right font-bold text-amber-800">{formatMWK(s.balanceDue)}</td>
                        <td className="p-2">{s.salesperson}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 5. Cashbook Report */}
          {selectedReport === 'cashbook_pnl' && (
            <div className="space-y-3">
              <h3 className="text-sm font-bold text-slate-900">Farm Cashbook Statement</h3>
              <div className="grid grid-cols-3 gap-2 bg-slate-50 p-3 rounded-lg border border-slate-200 text-xs">
                <div>
                  <span className="text-slate-500 uppercase text-[10px] font-semibold">Total Revenue Collected</span>
                  <p className="text-base font-black text-emerald-800 mt-0.5">
                    {formatMWK(db.sales.reduce((sum, s) => sum + s.paymentReceived, 0))}
                  </p>
                </div>
                <div>
                  <span className="text-slate-500 uppercase text-[10px] font-semibold">Total Expenses Incurred</span>
                  <p className="text-base font-black text-rose-900 mt-0.5">
                    {formatMWK(db.expenses.reduce((sum, e) => sum + e.amount, 0))}
                  </p>
                </div>
                <div>
                  <span className="text-slate-500 uppercase text-[10px] font-semibold">Estimated Net Operating Cash</span>
                  <p className="text-base font-black text-slate-900 mt-0.5">
                    {formatMWK(
                      db.sales.reduce((sum, s) => sum + s.paymentReceived, 0) -
                        db.expenses.reduce((sum, e) => sum + e.amount, 0)
                    )}
                  </p>
                </div>
              </div>
            </div>
          )}

          {/* 6. Feed Report */}
          {selectedReport === 'feed_consumption' && (
            <div className="space-y-3">
              <h3 className="text-sm font-bold text-slate-900">Feed Stock & Daily Consumption Log</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold">
                    <tr>
                      <th className="p-2">Date</th>
                      <th className="p-2">Flock</th>
                      <th className="p-2">Feed Product</th>
                      <th className="p-2 text-right">Quantity (kg)</th>
                      <th className="p-2">Recorded By</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {db.feedUsage.map((u) => (
                      <tr key={u.id}>
                        <td className="p-2">{formatDate(u.date)}</td>
                        <td className="p-2 font-semibold">{u.flockName}</td>
                        <td className="p-2">{u.productName}</td>
                        <td className="p-2 text-right font-black">{u.quantityUsedKg} kg</td>
                        <td className="p-2">{u.recordedBy}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}

          {/* 7. Mortality Report */}
          {selectedReport === 'mortality_analysis' && (
            <div className="space-y-3">
              <h3 className="text-sm font-bold text-slate-900">Flock Mortality & Loss Analysis</h3>
              <div className="overflow-x-auto">
                <table className="w-full text-left text-xs">
                  <thead className="bg-slate-50 border-b border-slate-200 text-slate-700 font-semibold">
                    <tr>
                      <th className="p-2">Date</th>
                      <th className="p-2">Flock</th>
                      <th className="p-2 text-right">Loss (Birds)</th>
                      <th className="p-2">Suspected Cause</th>
                      <th className="p-2">Observed Symptoms</th>
                      <th className="p-2">Action Taken</th>
                    </tr>
                  </thead>
                  <tbody className="divide-y divide-slate-100">
                    {db.mortalityRecords.map((m) => (
                      <tr key={m.id}>
                        <td className="p-2">{formatDate(m.date)}</td>
                        <td className="p-2 font-semibold">{m.flockName}</td>
                        <td className="p-2 text-right font-black text-rose-800">{m.numberOfBirds}</td>
                        <td className="p-2 font-medium">{m.suspectedCause}</td>
                        <td className="p-2 text-slate-600">{m.observedSymptoms || '-'}</td>
                        <td className="p-2 text-slate-600">{m.actionTaken}</td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            </div>
          )}
        </div>

        {/* Report Footer */}
        <div className="mt-8 pt-4 border-t border-slate-200 text-center text-[10px] text-slate-500">
          <p>Msantha Poultry Farm • Liwonde, Malawi • Isaac Chipeta (MD) & Sikizwayo Chipeta (Distribution)</p>
          <p className="mt-0.5">Offline-Ready Android System • Printed on {new Date().toLocaleString()}</p>
        </div>
      </div>
    </div>
  );
};
