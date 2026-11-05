/**
 * Msantha Poultry Farm Manager - Monthly Excel Spreadsheet Exporter
 * Generates formatted multi-sheet .xlsx workbooks for monthly egg production,
 * incubation performance, and livestock stock levels.
 */

import * as XLSX from 'xlsx';
import { FarmDatabase } from './storage';
import { formatDate, formatAge } from '../utils/i18n';

export function exportMonthlyFarmExcel(db: FarmDatabase, selectedMonthYear: string): void {
  // selectedMonthYear format: 'YYYY-MM' (e.g., '2026-10')
  const [yearStr, monthStr] = selectedMonthYear.split('-');
  const year = parseInt(yearStr, 10);
  const month = parseInt(monthStr, 10);

  const monthNames = [
    'January', 'February', 'March', 'April', 'May', 'June',
    'July', 'August', 'September', 'October', 'November', 'December'
  ];
  const monthLabel = `${monthNames[month - 1]} ${year}`;

  const workbook = XLSX.utils.book_new();

  // -------------------------------------------------------------
  // Sheet 1: Monthly Egg Production
  // -------------------------------------------------------------
  const eggCollectionsMonth = db.eggCollections.filter((e) =>
    e.collectionDate.startsWith(selectedMonthYear)
  );

  const eggRows = eggCollectionsMonth.map((e) => {
    const total = e.totalCollected;
    const saleable = e.saleableCount;
    const saleablePct = total > 0 ? `${((saleable / total) * 100).toFixed(1)}%` : '0%';
    const trays = (total / (db.settings.defaultTraySize || 30)).toFixed(1);

    return {
      'Date (DD/MM/YYYY)': formatDate(e.collectionDate),
      'Flock Name': e.flockName,
      'Species': e.species,
      'Breed / Line': e.breed,
      'Total Collected': total,
      'Standard Trays (30s)': parseFloat(trays),
      'Saleable Eggs': saleable,
      'Saleable Rate': saleablePct,
      'Cracked Shells': e.crackedCount,
      'Dirty Eggs': e.dirtyCount,
      'Damaged / Rejected': e.damagedRejectedCount,
      'Farm Kitchen Used': e.farmUsedCount || 0,
      'Incubation Reserved': e.reservedIncubationCount || 0,
      'Recorded By Staff': e.recordedBy,
      'Notes': e.notes || '',
    };
  });

  const eggWorksheet = XLSX.utils.json_to_sheet(
    eggRows.length > 0
      ? eggRows
      : [
          {
            'Notice': `No egg collection records recorded for ${monthLabel}.`,
          },
        ]
  );

  // Set column widths
  eggWorksheet['!cols'] = [
    { wch: 15 }, // Date
    { wch: 25 }, // Flock Name
    { wch: 18 }, // Species
    { wch: 20 }, // Breed
    { wch: 14 }, // Total Collected
    { wch: 16 }, // Trays
    { wch: 14 }, // Saleable
    { wch: 14 }, // Saleable Rate
    { wch: 14 }, // Cracked
    { wch: 12 }, // Dirty
    { wch: 16 }, // Damaged
    { wch: 16 }, // Farm Used
    { wch: 18 }, // Incubation Reserved
    { wch: 18 }, // Staff
    { wch: 30 }, // Notes
  ];

  XLSX.utils.book_append_sheet(workbook, eggWorksheet, 'Egg Production');

  // -------------------------------------------------------------
  // Sheet 2: Incubation Batches & Hatch Rates
  // -------------------------------------------------------------
  // Filter batches set or hatched in the selected month
  const customerBatchesMonth = db.customerIncubations.filter(
    (b) => b.dateSet.startsWith(selectedMonthYear) || b.expectedHatchDate.startsWith(selectedMonthYear)
  );

  const farmBatchesMonth = db.farmIncubations.filter(
    (b) => b.eggSettingDate.startsWith(selectedMonthYear) || b.expectedHatchDate.startsWith(selectedMonthYear)
  );

  const incubationRows: any[] = [];

  // Customer Batches
  customerBatchesMonth.forEach((b) => {
    const hatchRate =
      b.chicksHatched !== undefined && b.eggsAccepted > 0
        ? `${((b.chicksHatched / b.eggsAccepted) * 100).toFixed(1)}%`
        : 'In Incubation';

    incubationRows.push({
      'Batch Reference': b.batchCode,
      'Batch Category': 'Commercial Customer',
      'Customer / Source': b.customerName,
      'Customer Phone': b.customerPhone,
      'Customer Village': b.customerVillage || 'Liwonde',
      'Species': b.species,
      'Eggs Set / Accepted': b.eggsAccepted,
      'Eggs Rejected at Intake': b.eggsRejectedAtIntake,
      'Incubator Machine': b.incubatorId,
      'Setting Date': formatDate(b.dateSet),
      'Expected Hatch Date': formatDate(b.expectedHatchDate),
      'Fertile Eggs (Candling)': b.fertileEggsCount || 'Pending',
      'Chicks Hatched': b.chicksHatched !== undefined ? b.chicksHatched : 'Pending',
      'Chicks Delivered': b.chicksDelivered !== undefined ? b.chicksDelivered : 'Pending',
      'Hatch Success Rate': hatchRate,
      'Total Service Fee (MK)': b.totalCharge,
      'Amount Paid (MK)': b.amountPaid,
      'Balance Due (MK)': b.balanceDue,
      'Status': b.status.toUpperCase(),
      'Collection Done': b.isCollected ? 'YES' : 'NO',
    });
  });

  // Farm Batches
  farmBatchesMonth.forEach((b) => {
    const hatchRate =
      b.hatchPercentage !== undefined
        ? `${b.hatchPercentage}%`
        : b.chicksHatched !== undefined && b.eggsSet > 0
        ? `${((b.chicksHatched / b.eggsSet) * 100).toFixed(1)}%`
        : 'In Incubation';

    incubationRows.push({
      'Batch Reference': b.batchCode,
      'Batch Category': 'Farm-Owned Production',
      'Customer / Source': `Farm Flock (${b.breedSource})`,
      'Customer Phone': db.settings.phoneContact,
      'Customer Village': 'Msantha Farm, Liwonde',
      'Species': b.species,
      'Eggs Set / Accepted': b.eggsSet,
      'Eggs Rejected at Intake': 0,
      'Incubator Machine': b.incubatorId,
      'Setting Date': formatDate(b.eggSettingDate),
      'Expected Hatch Date': formatDate(b.expectedHatchDate),
      'Fertile Eggs (Candling)': b.firstCandlingResult?.fertileCount || 'Pending',
      'Chicks Hatched': b.chicksHatched !== undefined ? b.chicksHatched : 'Pending',
      'Chicks Delivered': b.chicksHatched !== undefined ? b.chicksHatched : 'Pending',
      'Hatch Success Rate': hatchRate,
      'Total Service Fee (MK)': 0,
      'Amount Paid (MK)': 0,
      'Balance Due (MK)': 0,
      'Status': b.status.toUpperCase(),
      'Collection Done': b.status === 'completed' ? 'YES' : 'NO',
    });
  });

  const incWorksheet = XLSX.utils.json_to_sheet(
    incubationRows.length > 0
      ? incubationRows
      : [{ 'Notice': `No incubation batches set or hatched in ${monthLabel}.` }]
  );

  incWorksheet['!cols'] = [
    { wch: 16 }, // Batch Ref
    { wch: 20 }, // Category
    { wch: 24 }, // Customer
    { wch: 18 }, // Phone
    { wch: 20 }, // Village
    { wch: 18 }, // Species
    { wch: 16 }, // Eggs Set
    { wch: 16 }, // Rejected
    { wch: 22 }, // Incubator
    { wch: 14 }, // Set Date
    { wch: 16 }, // Hatch Date
    { wch: 16 }, // Fertile
    { wch: 14 }, // Chicks Hatched
    { wch: 15 }, // Delivered
    { wch: 16 }, // Rate
    { wch: 18 }, // Total Fee
    { wch: 16 }, // Paid
    { wch: 16 }, // Balance
    { wch: 14 }, // Status
    { wch: 14 }, // Collected
  ];

  XLSX.utils.book_append_sheet(workbook, incWorksheet, 'Incubation & Hatches');

  // -------------------------------------------------------------
  // Sheet 3: Livestock & Flock Stocks
  // -------------------------------------------------------------
  const flockRows = db.flocks.map((f) => {
    // Calculate mortality losses for this flock
    const flockMortality = db.mortalityRecords
      .filter((m) => m.flockId === f.id)
      .reduce((sum, m) => sum + m.numberOfBirds, 0);

    const flockSales = db.movements
      .filter((m) => m.flockId === f.id && m.type === 'sold')
      .reduce((sum, m) => sum + m.quantity, 0);

    const lossPercentage =
      f.initialQuantity > 0 ? `${((flockMortality / f.initialQuantity) * 100).toFixed(1)}%` : '0%';

    return {
      'Flock Code': f.flockCode,
      'Flock Batch Name': f.name,
      'Species': f.species,
      'Breed / Line': f.breed,
      'Age (Calculated)': formatAge(f.dateAcquired),
      'Acquisition Date': formatDate(f.dateAcquired),
      'Housing / Shed': f.housingShed,
      'Purpose': f.purpose.toUpperCase(),
      'Current Live Birds': f.currentQuantity,
      'Initial Bird Intake': f.initialQuantity,
      'Total Mortality Losses': flockMortality,
      'Mortality Rate %': lossPercentage,
      'Birds Sold': flockSales,
      'Source / Supplier': f.sourceSupplier,
      'Flock Status': f.status.toUpperCase(),
      'Notes': f.notes || '',
    };
  });

  const flockWorksheet = XLSX.utils.json_to_sheet(flockRows);
  flockWorksheet['!cols'] = [
    { wch: 15 }, // Code
    { wch: 26 }, // Name
    { wch: 18 }, // Species
    { wch: 20 }, // Breed
    { wch: 15 }, // Age
    { wch: 15 }, // Date
    { wch: 18 }, // Shed
    { wch: 14 }, // Purpose
    { wch: 16 }, // Current
    { wch: 15 }, // Initial
    { wch: 16 }, // Mortality
    { wch: 15 }, // Mort Rate
    { wch: 14 }, // Sold
    { wch: 25 }, // Supplier
    { wch: 14 }, // Status
    { wch: 30 }, // Notes
  ];

  XLSX.utils.book_append_sheet(workbook, flockWorksheet, 'Livestock Stocks');

  // -------------------------------------------------------------
  // Sheet 4: Monthly Executive Summary & KPIs
  // -------------------------------------------------------------
  const totalEggsMonth = eggCollectionsMonth.reduce((sum, e) => sum + e.totalCollected, 0);
  const totalSaleableMonth = eggCollectionsMonth.reduce((sum, e) => sum + e.saleableCount, 0);
  const totalTraysMonth = (totalEggsMonth / (db.settings.defaultTraySize || 30)).toFixed(1);

  const totalBirdsFarm = db.flocks
    .filter((f) => f.status === 'active')
    .reduce((sum, f) => sum + f.currentQuantity, 0);

  const totalCustomerIncubationIncome = customerBatchesMonth.reduce(
    (sum, b) => sum + b.amountPaid,
    0
  );
  const totalCustomerIncubationReceivables = customerBatchesMonth.reduce(
    (sum, b) => sum + b.balanceDue,
    0
  );

  const totalSalesMonth = db.sales
    .filter((s) => s.date.startsWith(selectedMonthYear))
    .reduce((sum, s) => sum + s.total, 0);

  const totalExpensesMonth = db.expenses
    .filter((e) => e.date.startsWith(selectedMonthYear))
    .reduce((sum, e) => sum + e.amount, 0);

  const summaryRows = [
    { Metric: 'FARM IDENTITY & LOCATION', Value: `${db.settings.farmName} — Liwonde, Malawi` },
    { Metric: 'REPORTING PERIOD', Value: monthLabel },
    { Metric: 'MANAGING DIRECTOR', Value: db.settings.managingDirector },
    { Metric: 'DISTRIBUTION & MARKETING MANAGER', Value: db.settings.marketingManager },
    { Metric: 'CONTACT PHONE NUMBERS', Value: db.settings.phoneContact },
    { Metric: 'OFFICIAL CURRENCY', Value: 'Malawi Kwacha (MWK)' },
    { Metric: '----------------------------------------', Value: '----------------------------------------' },
    { Metric: 'TOTAL ACTIVE LIVE BIRDS ON FARM', Value: totalBirdsFarm },
    { Metric: 'ACTIVE FLOCKS / SHEDS REGISTERED', Value: db.flocks.filter((f) => f.status === 'active').length },
    { Metric: '----------------------------------------', Value: '----------------------------------------' },
    { Metric: `TOTAL EGGS PRODUCED (${monthLabel})`, Value: totalEggsMonth },
    { Metric: 'TOTAL STANDARD TRAYS PRODUCED', Value: parseFloat(totalTraysMonth) },
    { Metric: 'TOTAL SALEABLE EGGS', Value: totalSaleableMonth },
    { Metric: 'EGG SALEABLE QUALITY RATE', Value: totalEggsMonth > 0 ? `${((totalSaleableMonth / totalEggsMonth) * 100).toFixed(1)}%` : '0%' },
    { Metric: '----------------------------------------', Value: '----------------------------------------' },
    { Metric: 'COMMERCIAL CUSTOMER BATCHES IN MONTH', Value: customerBatchesMonth.length },
    { Metric: 'FARM INCUBATION BATCHES IN MONTH', Value: farmBatchesMonth.length },
    { Metric: 'CUSTOMER INCUBATION REVENUE COLLECTED (MK)', Value: totalCustomerIncubationIncome },
    { Metric: 'CUSTOMER INCUBATION UNCOLLECTED BALANCES (MK)', Value: totalCustomerIncubationReceivables },
    { Metric: '----------------------------------------', Value: '----------------------------------------' },
    { Metric: `TOTAL FARM SALES REVENUE (${monthLabel}) (MK)`, Value: totalSalesMonth },
    { Metric: `TOTAL FARM EXPENDITURES (${monthLabel}) (MK)`, Value: totalExpensesMonth },
    { Metric: 'ESTIMATED OPERATIONAL NET CASHFLOW (MK)', Value: totalSalesMonth + totalCustomerIncubationIncome - totalExpensesMonth },
  ];

  const summaryWorksheet = XLSX.utils.json_to_sheet(summaryRows);
  summaryWorksheet['!cols'] = [{ wch: 42 }, { wch: 38 }];

  XLSX.utils.book_append_sheet(workbook, summaryWorksheet, 'Monthly Summary');

  // Trigger browser download of .xlsx file
  const fileName = `msantha_farm_report_${selectedMonthYear}.xlsx`;
  XLSX.writeFile(workbook, fileName);
}
