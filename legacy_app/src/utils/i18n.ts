import { Language } from '../types';

export const translations = {
  en: {
    // Navigation & Common
    appName: 'Msantha Poultry Farm Manager',
    farmLocation: 'Liwonde, Malawi',
    dashboard: 'Dashboard',
    flocks: 'Flocks',
    eggs: 'Eggs',
    incubation: 'Incubation',
    customerIncubation: 'Customer Incubation',
    customers: 'Customers',
    feed: 'Feed & Stock',
    health: 'Health & Vaccines',
    mortality: 'Mortality Log',
    sales: 'Sales & Delivery',
    finance: 'Finance & Cashbook',
    tasks: 'Farm Tasks',
    reports: 'Reports & Export',
    settings: 'Settings',
    offline: 'Offline Mode (Local Storage Active)',
    online: 'Online',
    save: 'Save Record',
    cancel: 'Cancel',
    edit: 'Edit',
    delete: 'Delete',
    search: 'Search...',
    filter: 'Filter',
    exportCsv: 'Export CSV',
    printReport: 'Print / PDF',
    loading: 'Loading farm records...',
    all: 'All',
    total: 'Total',
    currency: 'MK',
    status: 'Status',
    actions: 'Actions',
    date: 'Date',
    notes: 'Notes',
    close: 'Close',
    details: 'Details',
    success: 'Saved successfully',
    error: 'Error saving record',

    // Roles
    roleManagingDirector: 'Isaac Chipeta (Managing Director)',
    roleMarketingManager: 'Sikizwayo Chipeta (Distribution & Marketing)',
    roleFarmWorker: 'Farm Operations Staff',
    accessRestricted: 'Access restricted to authorised managers',

    // Dashboard
    liveBirds: 'Live Birds',
    eggsCollectedToday: 'Eggs Collected Today',
    saleableRate: 'Saleable Eggs',
    eggsInStock: 'Eggs in Stock (Trays)',
    activeIncubationBatches: 'Incubating Batches',
    customerEggsInIncubation: 'Customer Eggs Incubating',
    feedRemaining: 'Feed Stock Balance',
    salesToday: 'Today\'s Sales',
    expensesToday: 'Today\'s Expenses',
    customerReceivables: 'Outstanding Balances',
    tasksDueToday: 'Tasks Due Today',
    quickActions: 'Quick Operations',
    recordEggCollection: 'Record Egg Collection',
    recordFeedUse: 'Record Feed Use',
    recordMortality: 'Record Bird Loss',
    addFlock: 'Add New Flock',
    newCustomerBatch: 'New Customer Incubation',
    recordSale: 'Record Sale',
    recordExpense: 'Record Expense',
    addTask: 'Schedule Task',

    // Flocks
    flockRegister: 'Flock & Poultry Register',
    flockName: 'Flock Name',
    species: 'Species',
    breed: 'Breed / Line',
    age: 'Age',
    housing: 'Housing / Shed',
    currentBirds: 'Current Birds',
    purpose: 'Purpose',
    addBirdMovement: 'Record Bird Movement / Adjustment',
    daysOld: 'days',
    weeksOld: 'weeks',

    // Eggs
    eggCollectionRegister: 'Daily Egg Collection Register',
    eggsCollected: 'Eggs Collected',
    saleable: 'Saleable',
    cracked: 'Cracked',
    dirty: 'Dirty',
    rejected: 'Damaged / Rejected',
    farmUsed: 'Farm Used',
    reservedIncubation: 'Reserved for Incubation',
    trayCount: 'Trays (30 eggs/tray)',
    trayCapacityHelp: 'Standard tray = 30 eggs',

    // Incubation
    farmIncubationTitle: 'Farm Incubation Batches',
    customerIncubationTitle: 'Commercial Customer Incubation Register',
    batchCode: 'Batch Code',
    eggSettingDate: 'Setting Date',
    eggsSet: 'Eggs Set',
    incubator: 'Incubator ID',
    candling: 'Candling',
    expectedHatch: 'Expected Hatch',
    hatchRate: 'Hatch Rate',
    lockdownDate: 'Lockdown Date',
    chicksHatched: 'Chicks Hatched',
    shareWhatsAppReceipt: 'Share WhatsApp Receipt',

    // Customer Incubation
    customerName: 'Customer Name',
    phone: 'Phone Number',
    village: 'Village / Town',
    eggsReceived: 'Eggs Received',
    eggsAccepted: 'Eggs Accepted',
    rejectedIntake: 'Rejected at Intake',
    totalCharge: 'Total Charge',
    depositPaid: 'Deposit Paid',
    balanceDue: 'Balance Due',
    paymentHistory: 'Payment History',
    recordPayment: 'Record Payment',
    markCollected: 'Mark Collected',

    // Feed & Health
    feedInventory: 'Feed Inventory & Stock',
    dailyFeedConsumption: 'Daily Consumption Log',
    healthAndTreatment: 'Health & Medication Log',
    vaccinationSchedule: 'Vaccination Schedule',
    mortalityRate: 'Mortality Rate',
    suspectedCause: 'Suspected Cause',
    actionTaken: 'Action Taken',
    disclaimerVeterinary: 'Veterinary advice and medicine packaging instructions must always take precedence.',

    // Sales & Finance
    salesRegister: 'Sales & Invoices',
    productSold: 'Product Sold',
    quantity: 'Quantity',
    unitPrice: 'Unit Price',
    paymentMethod: 'Payment Method',
    deliveryStatus: 'Delivery Status',
    cashbook: 'Farm Cashbook & Ledger',
    income: 'Income',
    expenses: 'Expenses',
    netCashflow: 'Estimated Net Position',

    // Tasks
    taskManager: 'Farm Tasks & Calendar',
    dueToday: 'Due Today',
    upcoming: 'Upcoming',
    overdue: 'Overdue',
    completed: 'Completed',

    // Settings & Backup
    backupAndRestore: 'Backup & Restore Farm Records',
    downloadBackup: 'Download Backup JSON',
    restoreBackup: 'Restore from Backup File',
    seedSampleData: 'Load Msantha Farm Demo Data',
    resetData: 'Clear All Data',
  },
  ny: {
    // Chichewa translations
    appName: 'Msantha Poultry Farm Manager',
    farmLocation: 'Liwonde, Malawi',
    dashboard: 'Chiwonetsero (Home)',
    flocks: 'Ziweto (Flocks)',
    eggs: 'Mazira',
    incubation: 'Kuswa Mazira',
    customerIncubation: 'Kuswetsa Mazira a Makasitomala',
    customers: 'Makasitomala',
    feed: 'Chakudya cha Mbalame',
    health: 'Za Umoyo & Mankhwala',
    mortality: 'Mbalame Zomwalira',
    sales: 'Malonda',
    finance: 'Ndalama (Cashbook)',
    tasks: 'Ntchito za pa Famu',
    reports: 'Malipoti',
    settings: 'Zosintha / Settings',
    offline: 'Mumagwira Popanda Intaneti (Offline)',
    online: 'Pa Intaneti',
    save: 'Sungani',
    cancel: 'Lekani',
    edit: 'Sinthani',
    delete: 'Chotsani',
    search: 'Fufuzani...',
    filter: 'Sankhani',
    exportCsv: 'Koperani ku CSV',
    printReport: 'Sindikizani Lipoti',
    loading: 'Kutsegula zolemba...',
    all: 'Zonse',
    total: 'Chiwerengero',
    currency: 'MK',
    status: 'Mmene zilili',
    actions: 'Zochita',
    date: 'Tsiku',
    notes: 'Zowonjezera',
    close: 'Tsekani',
    details: 'Tsatanetsatane',
    success: 'Zasungidwa bwino',
    error: 'Zalephera kusunga',

    // Roles
    roleManagingDirector: 'Isaac Chipeta (Bwana Wamkulu)',
    roleMarketingManager: 'Sikizwayo Chipeta (Woyang\'anira Malonda)',
    roleFarmWorker: 'Wogwira Ntchito pa Famu',
    accessRestricted: 'Malo oletsedwa kwa ogwira ntchito wamba',

    // Dashboard
    liveBirds: 'Mbalame Zonse Zamoyo',
    eggsCollectedToday: 'Mazira Osonkhanitsidwa Lero',
    saleableRate: 'Mazira Ogulitsika',
    eggsInStock: 'Mazira Omwe Alipo (Mathireyi)',
    activeIncubationBatches: 'Makina Akuswa Mazira',
    customerEggsInIncubation: 'Mazira a Makasitomala m\'Makina',
    feedRemaining: 'Chakudya Chotsala',
    salesToday: 'Malonda a Lero',
    expensesToday: 'Zogulira za Lero',
    customerReceivables: 'Ndalama za Makasitomala',
    tasksDueToday: 'Ntchito za Lero',
    quickActions: 'Zochita Mwachangu',
    recordEggCollection: 'Lembani Mazira a Lero',
    recordFeedUse: 'Lembani Chakudya Chogwiritsidwa',
    recordMortality: 'Lembani Zomwalira',
    addFlock: 'Onjezani Gulu Latsopano',
    newCustomerBatch: 'Bwezani Mazira a Kasitomala',
    recordSale: 'Lembani Zogulitsa',
    recordExpense: 'Lembani Zowonongera',
    addTask: 'Lembani Ntchito',

    // Flocks
    flockRegister: 'Kaundula wa Magulu a Mbalame',
    flockName: 'Dzina la Gulu',
    species: 'Mtundu wa Mbalame',
    breed: 'Mtundu Weniweni (Breed)',
    age: 'Msinkhu',
    housing: 'Khola / Nyumba',
    currentBirds: 'Mbalame Zomwe Zilipo',
    purpose: 'Cholinga',
    addBirdMovement: 'Lembani Zosamutsidwa kapena Zosintha',
    daysOld: 'masiku',
    weeksOld: 'masabata',

    // Eggs
    eggCollectionRegister: 'Kaundula wa Kusonkhanitsa Mazira',
    eggsCollected: 'Mazira Osonkhanitsidwa',
    saleable: 'Ogulitsika',
    cracked: 'Osweka pang\'ono',
    dirty: 'Odetsedwa',
    rejected: 'Oonongeka kwambiri',
    farmUsed: 'Odyedwa pa famu',
    reservedIncubation: 'Osungira makina obisalira',
    trayCount: 'Mathireyi (Mazira 30)',
    trayCapacityHelp: 'Thireyi imodzi imakhala ndi mazira 30',

    // Incubation
    farmIncubationTitle: 'Mazira a Famu M\'makina',
    customerIncubationTitle: 'Kaundula wa Kuswetsa Mazira a Makasitomala',
    batchCode: 'Nambala ya Batch',
    eggSettingDate: 'Tsiku Lowika m\'Makina',
    eggsSet: 'Mazira Oikidwa',
    incubator: 'Makina Oikidwa',
    candling: 'Kuyeza ndi Kuwala (Candling)',
    expectedHatch: 'Tsiku Loyembekezera Kuswa',
    hatchRate: 'Mlingo Woswa',
    lockdownDate: 'Tsiku Loimika Kutembenuza (Lockdown)',
    chicksHatched: 'Anapiye Osweka',
    shareWhatsAppReceipt: 'Tumizani Lisiti pa WhatsApp',

    // Customer Incubation
    customerName: 'Dzina la Kasitomala',
    phone: 'Lamya / Nambala',
    village: 'Mudzi / Tawuni',
    eggsReceived: 'Mazira Olandiridwa',
    eggsAccepted: 'Mazira Ololedwa m\'Makina',
    rejectedIntake: 'Mazira Okanidwa',
    totalCharge: 'Mtengo Wonse',
    depositPaid: 'Ndalama Yoyamba (Deposit)',
    balanceDue: 'Ndalama Yotsala',
    paymentHistory: 'Mbiri ya Malipiro',
    recordPayment: 'Lembani Malipiro',
    markCollected: 'Kasitomala Watenga',

    // Feed & Health
    feedInventory: 'Katundu wa Chakudya',
    dailyFeedConsumption: 'Kadyedwe ka Tsiku ndi Tsiku',
    healthAndTreatment: 'Kaundula wa Umoyo ndi Mankhwala',
    vaccinationSchedule: 'Ndandanda ya Katemera',
    mortalityRate: 'Mlingo Womwalira',
    suspectedCause: 'Chomwe Chikuoneka Kuti Chaphetsa',
    actionTaken: 'Chomwe Chachitika',
    disclaimerVeterinary: 'Malangizo a dokotala wa ziweto kapena apakatoni azitsogola nthawi zonse.',

    // Sales & Finance
    salesRegister: 'Kaundula wa Zogulitsa',
    productSold: 'Zomwe Zagulitsidwa',
    quantity: 'Kuchuluka',
    unitPrice: 'Mtengo pa Chilichonse',
    paymentMethod: 'Njira Yolipirira (Airtel, TNM, Ndalama m\'manja)',
    deliveryStatus: 'Mmene Zafikira',
    cashbook: 'Buku la Ndalama (Cashbook)',
    income: 'Zolowa (Ndalama Zopezeka)',
    expenses: 'Zotuluka (Zogulira pa Famu)',
    netCashflow: 'Phindu Loyembekezeka',

    // Tasks
    taskManager: 'Ntchito za pa Famu',
    dueToday: 'Zochitika Lero',
    upcoming: 'Zikubwera',
    overdue: 'Zachedwa',
    completed: 'Zamalizidwa',

    // Settings & Backup
    backupAndRestore: 'Kusunga & Kubwezeretsa Zolemba (Backup)',
    downloadBackup: 'Koperani Zolemba Zonse (Download)',
    restoreBackup: 'Bwezeretsani Zolemba kuchokera pa Fayilo',
    seedSampleData: 'Ikani Zitsanzo za Msantha Farm',
    resetData: 'Fufutani Zolemba Zonse',
  },
};

export function getTranslation(lang: Language, key: keyof typeof translations['en']): string {
  const langDict = translations[lang] || translations.en;
  return langDict[key] || translations.en[key] || String(key);
}

export function formatMWK(amount: number): string {
  return `MK ${amount.toLocaleString('en-MW')}`;
}

export function formatDate(dateString: string): string {
  if (!dateString) return '-';
  const parts = dateString.split('-');
  if (parts.length === 3) {
    return `${parts[2]}/${parts[1]}/${parts[0]}`; // DD/MM/YYYY
  }
  return dateString;
}

export function calculateDaysAge(dateAcquired: string): number {
  if (!dateAcquired) return 0;
  const acquired = new Date(dateAcquired).getTime();
  const now = new Date().getTime();
  const diffDays = Math.floor((now - acquired) / (1000 * 60 * 60 * 24));
  return Math.max(0, diffDays);
}

export function formatAge(dateAcquired: string): string {
  const days = calculateDaysAge(dateAcquired);
  if (days < 14) {
    return `${days} days`;
  }
  const weeks = Math.floor(days / 7);
  const remainingDays = days % 7;
  return remainingDays > 0 ? `${weeks} wks, ${remainingDays} d` : `${weeks} weeks`;
}
