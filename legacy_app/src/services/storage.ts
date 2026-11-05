/**
 * Msantha Poultry Farm Manager - Persistent Storage Service
 * Supports offline storage, JSON import/export, and farm seed data.
 */

import {
  AuditLog,
  Customer,
  CustomerIncubationBatch,
  EggCollection,
  Expense,
  FarmIncubationBatch,
  FarmSettings,
  FarmTask,
  FeedItem,
  FeedUsage,
  Flock,
  FlockMovement,
  HealthRecord,
  Language,
  MortalityRecord,
  Sale,
  Supplier,
  UserProfile,
  VaccinationEvent,
} from '../types';

export const DEFAULT_USERS: UserProfile[] = [
  {
    id: 'user_isaac',
    name: 'Isaac Chipeta',
    username: 'isaac',
    pin: '1122',
    role: 'managing_director',
    title: 'Managing Director & Farm Owner',
    phone: '+265 999 11 22 33',
    avatarColor: '#1b4332',
    isViewOnly: false,
  },
  {
    id: 'user_sikizwayo',
    name: 'Sikizwayo Chipeta',
    username: 'sikizwayo',
    pin: '8899',
    role: 'marketing_manager',
    title: 'Sales & Distribution Manager',
    phone: '+265 888 44 55 66',
    avatarColor: '#b45309',
    isViewOnly: false,
  },
  {
    id: 'user_worker',
    name: 'Farm Operations Staff',
    username: 'staff',
    pin: '0000',
    role: 'viewer',
    title: 'Operations Viewer (View-Only)',
    phone: '+265 995 77 88 99',
    avatarColor: '#0f766e',
    isViewOnly: true,
  },
];

export const DEFAULT_SETTINGS: FarmSettings = {
  farmName: 'Msantha Poultry Farm',
  location: 'Liwonde, Malawi',
  managingDirector: 'Isaac Chipeta',
  marketingManager: 'Sikizwayo Chipeta',
  currency: 'MWK',
  currencySymbol: 'MK',
  defaultTraySize: 30,
  lowFeedThresholdKg: 100,
  lowEggStockAlertTrays: 10,
  phoneContact: '+265 999 11 22 33 / +265 888 44 55 66',
  emailContact: 'msanthapoultry@gmail.com',
  speciesIncubationDays: {
    'Mikolongwe chicken': 21,
    'Sasso chicken': 21,
    'Local chicken': 21,
    'Turkey': 28,
    'Duck': 28,
    'Quail': 17,
    'Guinea fowl': 28,
    'Other': 21,
  },
  trayPriceMwk: 4500,
  eggUnitPriceMwk: 150,
};

export interface FarmDatabase {
  version: number;
  lastUpdated: number;
  settings: FarmSettings;
  currentUserId: string;
  users: UserProfile[];
  isAuthenticated: boolean;
  dismissedAlertIds?: string[];
  language: Language;
  flocks: Flock[];
  movements: FlockMovement[];
  eggCollections: EggCollection[];
  farmIncubations: FarmIncubationBatch[];
  customers: Customer[];
  customerIncubations: CustomerIncubationBatch[];
  feedItems: FeedItem[];
  feedUsage: FeedUsage[];
  healthRecords: HealthRecord[];
  vaccinationEvents: VaccinationEvent[];
  mortalityRecords: MortalityRecord[];
  sales: Sale[];
  suppliers: Supplier[];
  expenses: Expense[];
  tasks: FarmTask[];
  auditLogs: AuditLog[];
}

const STORAGE_KEY = 'msantha_poultry_farm_v1';

// Today's date helper in YYYY-MM-DD
function getTodayIso(daysOffset = 0): string {
  const d = new Date();
  d.setDate(d.getDate() + daysOffset);
  return d.toISOString().split('T')[0];
}

export function createInitialSeedData(): FarmDatabase {
  const today = getTodayIso();
  const threeDaysAgo = getTodayIso(-3);
  const tenDaysAgo = getTodayIso(-10);
  const twentyDaysAgo = getTodayIso(-20);
  const fiftyDaysAgo = getTodayIso(-50);
  const ninetyDaysAgo = getTodayIso(-90);

  const flocks: Flock[] = [
    {
      id: 'flk_01',
      flockCode: 'FLK-2026-001',
      name: 'Mikolongwe Layers Shed A',
      species: 'Mikolongwe chicken',
      breed: 'Mikolongwe Layer Line',
      dateAcquired: ninetyDaysAgo,
      sourceSupplier: 'Bvumbwe Agricultural Research Station',
      initialQuantity: 360,
      currentQuantity: 348,
      housingShed: 'Shed 1 - Deep Litter',
      purpose: 'eggs',
      ageGroup: 'laying_hens',
      status: 'active',
      notes: 'High vigor layers, robust to local Liwonde climate.',
      createdAt: Date.now() - 90 * 86400000,
    },
    {
      id: 'flk_02',
      flockCode: 'FLK-2026-002',
      name: 'Sasso Growers Yard B',
      species: 'Sasso chicken',
      breed: 'Sasso Dual-Purpose',
      dateAcquired: fiftyDaysAgo,
      sourceSupplier: 'Central Poultry Hatchery Lilongwe',
      initialQuantity: 280,
      currentQuantity: 275,
      housingShed: 'Shed 2 - Semi Free Range',
      purpose: 'dual_purpose',
      ageGroup: 'growers',
      status: 'active',
      notes: 'Growing fast, healthy appetite on grower mash.',
      createdAt: Date.now() - 50 * 86400000,
    },
    {
      id: 'flk_03',
      flockCode: 'FLK-2026-003',
      name: 'Local Malawi Free-Range Breeders',
      species: 'Local chicken',
      breed: 'Indigenous Malawi Cross',
      dateAcquired: ninetyDaysAgo,
      sourceSupplier: 'Local Liwonde smallholders',
      initialQuantity: 140,
      currentQuantity: 138,
      housingShed: 'Yard 3 - Traditional Run',
      purpose: 'breeding',
      ageGroup: 'breeding_hens',
      status: 'active',
      notes: 'Parent stock for hardy indigenous chick production.',
      createdAt: Date.now() - 90 * 86400000,
    },
    {
      id: 'flk_04',
      flockCode: 'FLK-2026-004',
      name: 'Heritage Turkeys Run',
      species: 'Turkey',
      breed: 'Broad Breasted Bronze',
      dateAcquired: twentyDaysAgo,
      sourceSupplier: 'Zomba Poultry Centre',
      initialQuantity: 35,
      currentQuantity: 35,
      housingShed: 'Paddock 4',
      purpose: 'meat',
      ageGroup: 'turkey_poults',
      status: 'active',
      notes: 'High market demand for festive season in Blantyre & Zomba.',
      createdAt: Date.now() - 20 * 86400000,
    },
    {
      id: 'flk_05',
      flockCode: 'FLK-2026-005',
      name: 'Khaki Campbell Ducks',
      species: 'Duck',
      breed: 'Khaki Campbell',
      dateAcquired: fiftyDaysAgo,
      sourceSupplier: 'Shire River Waterfowl',
      initialQuantity: 45,
      currentQuantity: 45,
      housingShed: 'Pond Enclosure A',
      purpose: 'eggs',
      ageGroup: 'laying_hens',
      status: 'active',
      notes: 'Very high egg output, resilient against disease.',
      createdAt: Date.now() - 50 * 86400000,
    },
    {
      id: 'flk_06',
      flockCode: 'FLK-2026-006',
      name: 'Japanese Coturnix Quails',
      species: 'Quail',
      breed: 'Coturnix Japonica',
      dateAcquired: twentyDaysAgo,
      sourceSupplier: 'Blantyre Exotics',
      initialQuantity: 120,
      currentQuantity: 118,
      housingShed: 'Aviary Tier 1',
      purpose: 'eggs',
      ageGroup: 'adult_quails',
      status: 'active',
      notes: 'Laying nutritious eggs sold to pharmacies and hotels in Liwonde.',
      createdAt: Date.now() - 20 * 86400000,
    },
  ];

  const movements: FlockMovement[] = [
    {
      id: 'mov_01',
      flockId: 'flk_01',
      date: ninetyDaysAgo,
      type: 'initial',
      quantity: 360,
      previousQuantity: 0,
      newQuantity: 360,
      destinationShed: 'Shed 1',
      reason: 'Flock received from Bvumbwe',
      recordedBy: 'Isaac Chipeta',
      timestamp: Date.now() - 90 * 86400000,
    },
    {
      id: 'mov_02',
      flockId: 'flk_01',
      date: tenDaysAgo,
      type: 'mortality',
      quantity: 4,
      previousQuantity: 352,
      newQuantity: 348,
      reason: 'Heat spike in early afternoon',
      recordedBy: 'Farm Operations Staff',
      timestamp: Date.now() - 10 * 86400000,
    },
  ];

  const eggCollections: EggCollection[] = [
    {
      id: 'egg_01',
      collectionDate: today,
      collectionTime: '08:30',
      flockId: 'flk_01',
      flockName: 'Mikolongwe Layers Shed A',
      species: 'Mikolongwe chicken',
      breed: 'Mikolongwe Layer Line',
      totalCollected: 275,
      saleableCount: 255,
      crackedCount: 8,
      dirtyCount: 6,
      damagedRejectedCount: 6,
      farmUsedCount: 15,
      reservedIncubationCount: 40,
      recordedBy: 'Farm Operations Staff',
      notes: 'Morning collection, shells very firm after oyster shell supplement.',
      createdAt: Date.now() - 12000000,
    },
    {
      id: 'egg_02',
      collectionDate: today,
      collectionTime: '09:00',
      flockId: 'flk_05',
      flockName: 'Khaki Campbell Ducks',
      species: 'Duck',
      breed: 'Khaki Campbell',
      totalCollected: 38,
      saleableCount: 36,
      crackedCount: 1,
      dirtyCount: 1,
      damagedRejectedCount: 0,
      farmUsedCount: 0,
      reservedIncubationCount: 20,
      recordedBy: 'Farm Operations Staff',
      notes: 'Duck eggs washed and sorted for restaurant customer.',
      createdAt: Date.now() - 10000000,
    },
    {
      id: 'egg_03',
      collectionDate: getTodayIso(-1),
      collectionTime: '08:45',
      flockId: 'flk_01',
      flockName: 'Mikolongwe Layers Shed A',
      species: 'Mikolongwe chicken',
      breed: 'Mikolongwe Layer Line',
      totalCollected: 282,
      saleableCount: 265,
      crackedCount: 7,
      dirtyCount: 5,
      damagedRejectedCount: 5,
      farmUsedCount: 10,
      reservedIncubationCount: 30,
      recordedBy: 'Farm Operations Staff',
      notes: 'Good laying rate (81%).',
      createdAt: Date.now() - 86400000,
    },
  ];

  const farmIncubations: FarmIncubationBatch[] = [
    {
      id: 'inc_f_01',
      batchCode: 'INC-2026-001',
      species: 'Mikolongwe chicken',
      breedSource: 'Flock 1 (Mikolongwe Layers)',
      flockId: 'flk_01',
      eggSettingDate: getTodayIso(-14),
      eggsSet: 240,
      incubatorId: 'Incubator 1 (Digital Solar 500)',
      expectedHatchDate: getTodayIso(7),
      incubationDurationDays: 21,
      firstCandlingDate: getTodayIso(-7),
      firstCandlingResult: {
        fertileCount: 216,
        infertileCount: 24,
        dateRecorded: getTodayIso(-7),
      },
      lockdownDate: getTodayIso(4),
      status: 'candled',
      notes: '90% fertility on Day 7 candling. Humidity stable at 55%.',
      createdAt: Date.now() - 14 * 86400000,
    },
    {
      id: 'inc_f_02',
      batchCode: 'INC-2026-002',
      species: 'Local chicken',
      breedSource: 'Flock 3 (Local Breeders)',
      flockId: 'flk_03',
      eggSettingDate: getTodayIso(-19),
      eggsSet: 120,
      incubatorId: 'Incubator 2 (Automatic 300)',
      expectedHatchDate: getTodayIso(2),
      incubationDurationDays: 21,
      firstCandlingDate: getTodayIso(-12),
      firstCandlingResult: {
        fertileCount: 108,
        infertileCount: 12,
        dateRecorded: getTodayIso(-12),
      },
      secondCandlingDate: getTodayIso(-5),
      secondCandlingResult: {
        viableCount: 102,
        deadInShellCount: 6,
        dateRecorded: getTodayIso(-5),
      },
      lockdownDate: getTodayIso(-1),
      status: 'lockdown',
      notes: 'Turners switched off. Humidity elevated to 70% for hatching.',
      createdAt: Date.now() - 19 * 86400000,
    },
  ];

  const customers: Customer[] = [
    {
      id: 'cust_01',
      customerCode: 'CUST-001',
      name: 'Mrs. Mary Banda',
      phone: '+265 991 234 567',
      addressVillage: 'Machinga Turn-off, Liwonde',
      category: 'commercial_farmer',
      notes: 'Brings batches of local eggs monthly for village poultry project.',
      createdAt: Date.now() - 40 * 86400000,
    },
    {
      id: 'cust_02',
      customerCode: 'CUST-002',
      name: 'Mr. Patrick Phiri',
      phone: '+265 884 567 890',
      addressVillage: 'Ulongwe Trading Centre',
      category: 'commercial_farmer',
      notes: 'Specializes in turkey and guinea fowl multiplication.',
      createdAt: Date.now() - 30 * 86400000,
    },
    {
      id: 'cust_03',
      customerCode: 'CUST-003',
      name: 'Shire River Safari Lodge',
      phone: '+265 999 876 543',
      addressVillage: 'Liwonde National Park Road',
      category: 'restaurant',
      notes: 'Buys 10 trays of fresh eggs weekly and dressed ducks.',
      createdAt: Date.now() - 60 * 86400000,
    },
    {
      id: 'cust_04',
      customerCode: 'CUST-004',
      name: 'Chifundo Mwale',
      phone: '+265 881 334 455',
      addressVillage: 'Liwonde Market stall #14',
      category: 'vendor',
      notes: 'Retail vendor of eggs and live birds.',
      createdAt: Date.now() - 25 * 86400000,
    },
  ];

  const customerIncubations: CustomerIncubationBatch[] = [
    {
      id: 'cust_inc_01',
      batchCode: 'CUST-INC-041',
      customerId: 'cust_01',
      customerName: 'Mrs. Mary Banda',
      customerPhone: '+265 991 234 567',
      customerVillage: 'Machinga Turn-off, Liwonde',
      species: 'Local chicken',
      breed: 'Malawi Village Cross',
      dateReceived: getTodayIso(-16),
      dateSet: getTodayIso(-15),
      eggsReceived: 150,
      eggsAccepted: 140,
      eggsRejectedAtIntake: 10,
      rejectionReason: '6 cracked shells during bicycle transport, 4 excessively dirty/small.',
      incubatorId: 'Incubator 1 (Digital Solar 500)',
      durationDays: 21,
      expectedHatchDate: getTodayIso(6),
      plannedCollectionDate: getTodayIso(7),
      fertileEggsCount: 122,
      pricingMethod: 'per_egg',
      unitRate: 250, // 250 MWK per egg set
      totalCharge: 35000, // 140 * 250
      deposit: 20000,
      amountPaid: 20000,
      balanceDue: 15000,
      payments: [
        {
          id: 'pay_01',
          date: getTodayIso(-15),
          amount: 20000,
          method: 'airtel_money',
          receiptNumber: 'AIRTEL-TX-998124',
          recordedBy: 'Sikizwayo Chipeta',
          notes: 'Initial 57% deposit paid on setting.',
          timestamp: Date.now() - 15 * 86400000,
        },
      ],
      status: 'candled',
      isCollected: false,
      notes: 'Customer notified via SMS about candling results (122 fertile).',
      recordedBy: 'Sikizwayo Chipeta',
      createdAt: Date.now() - 16 * 86400000,
    },
    {
      id: 'cust_inc_02',
      batchCode: 'CUST-INC-042',
      customerId: 'cust_02',
      customerName: 'Mr. Patrick Phiri',
      customerPhone: '+265 884 567 890',
      customerVillage: 'Ulongwe Trading Centre',
      species: 'Turkey',
      breed: 'Bronze Heritage',
      dateReceived: getTodayIso(-25),
      dateSet: getTodayIso(-25),
      eggsReceived: 65,
      eggsAccepted: 60,
      eggsRejectedAtIntake: 5,
      rejectionReason: 'Thin shells and misshapen',
      incubatorId: 'Incubator 2 (Automatic 300)',
      durationDays: 28,
      expectedHatchDate: getTodayIso(3),
      plannedCollectionDate: getTodayIso(4),
      fertileEggsCount: 52,
      pricingMethod: 'per_egg',
      unitRate: 500, // 500 MWK per turkey egg
      totalCharge: 30000,
      deposit: 30000,
      amountPaid: 30000,
      balanceDue: 0,
      payments: [
        {
          id: 'pay_02',
          date: getTodayIso(-25),
          amount: 30000,
          method: 'tnm_mpamba',
          receiptNumber: 'MPAMBA-REF-44219',
          recordedBy: 'Isaac Chipeta',
          notes: 'Full payment up front via TNM Mpamba.',
          timestamp: Date.now() - 25 * 86400000,
        },
      ],
      status: 'lockdown',
      isCollected: false,
      notes: 'Lockdown started on Day 25. Expected 45-50 healthy turkey poults.',
      recordedBy: 'Isaac Chipeta',
      createdAt: Date.now() - 25 * 86400000,
    },
    {
      id: 'cust_inc_03',
      batchCode: 'CUST-INC-039',
      customerId: 'cust_04',
      customerName: 'Chifundo Mwale',
      customerPhone: '+265 881 334 455',
      customerVillage: 'Liwonde Market stall #14',
      species: 'Mikolongwe chicken',
      breed: 'Mikolongwe Dual',
      dateReceived: getTodayIso(-25),
      dateSet: getTodayIso(-24),
      eggsReceived: 100,
      eggsAccepted: 100,
      eggsRejectedAtIntake: 0,
      incubatorId: 'Incubator 1 (Digital Solar 500)',
      durationDays: 21,
      expectedHatchDate: getTodayIso(-3),
      plannedCollectionDate: getTodayIso(-2),
      actualCollectionDate: getTodayIso(-2),
      fertileEggsCount: 92,
      chicksHatched: 84,
      chicksDelivered: 84,
      unhatchedEggs: 16,
      status: 'collected',
      pricingMethod: 'per_chick',
      unitRate: 400, // 400 MWK per chick hatched
      totalCharge: 33600, // 84 * 400
      deposit: 15000,
      amountPaid: 33600,
      balanceDue: 0,
      payments: [
        {
          id: 'pay_03a',
          date: getTodayIso(-24),
          amount: 15000,
          method: 'cash',
          receiptNumber: 'REC-0988',
          recordedBy: 'Sikizwayo Chipeta',
          notes: 'Deposit',
          timestamp: Date.now() - 24 * 86400000,
        },
        {
          id: 'pay_03b',
          date: getTodayIso(-2),
          amount: 18600,
          method: 'cash',
          receiptNumber: 'REC-1014',
          recordedBy: 'Sikizwayo Chipeta',
          notes: 'Final balance upon picking 84 lively chicks',
          timestamp: Date.now() - 2 * 86400000,
        },
      ],
      isCollected: true,
      notes: 'Customer extremely satisfied with 84% hatch rate.',
      recordedBy: 'Sikizwayo Chipeta',
      createdAt: Date.now() - 25 * 86400000,
    },
  ];

  const feedItems: FeedItem[] = [
    {
      id: 'feed_01',
      productName: 'Farmers World Layer Mash (50kg)',
      feedType: 'layer_feed',
      supplier: 'Farmers World Liwonde Branch',
      bagSizeKg: 50,
      costPerBag: 38500,
      currentStockKg: 850, // 17 bags
      lowStockAlertKg: 200,
      storageLocation: 'Feed Store Room 1',
      lastRestockedDate: getTodayIso(-5),
    },
    {
      id: 'feed_02',
      productName: 'Astra Chick Starter Crumbles (50kg)',
      feedType: 'chick_starter',
      supplier: 'Rab Processors Zomba',
      bagSizeKg: 50,
      costPerBag: 42000,
      currentStockKg: 350, // 7 bags
      lowStockAlertKg: 100,
      storageLocation: 'Feed Store Room 1',
      lastRestockedDate: getTodayIso(-12),
    },
    {
      id: 'feed_03',
      productName: 'Central Poultry Broiler Grower Mash (50kg)',
      feedType: 'grower_feed',
      supplier: 'Central Poultry Depot',
      bagSizeKg: 50,
      costPerBag: 39500,
      currentStockKg: 400,
      lowStockAlertKg: 150,
      storageLocation: 'Feed Store Room 2',
      lastRestockedDate: getTodayIso(-8),
    },
  ];

  const feedUsage: FeedUsage[] = [
    {
      id: 'fu_01',
      date: today,
      flockId: 'flk_01',
      flockName: 'Mikolongwe Layers Shed A',
      feedItemId: 'feed_01',
      productName: 'Farmers World Layer Mash (50kg)',
      quantityUsedKg: 42,
      recordedBy: 'Farm Operations Staff',
      notes: 'Morning and afternoon ration.',
      createdAt: Date.now() - 14400000,
    },
    {
      id: 'fu_02',
      date: today,
      flockId: 'flk_02',
      flockName: 'Sasso Growers Yard B',
      feedItemId: 'feed_03',
      productName: 'Central Poultry Broiler Grower Mash (50kg)',
      quantityUsedKg: 35,
      recordedBy: 'Farm Operations Staff',
      notes: 'Greens supplemented from garden.',
      createdAt: Date.now() - 14000000,
    },
  ];

  const healthRecords: HealthRecord[] = [
    {
      id: 'hlth_01',
      date: getTodayIso(-10),
      flockId: 'flk_01',
      flockName: 'Mikolongwe Layers Shed A',
      healthIssue: 'Mild Heat Stress Symptoms',
      symptomsObserved: 'Panting during hot peak afternoon, slight reduction in feed consumption',
      medicineOrVaccine: 'Vitalyte Multivitamin & Electrolytes Powder',
      batchNumber: 'VYT-2026-90',
      expiryDate: '2027-05-30',
      quantityAdministered: '100g in 50 Litres of drinking water',
      dosageAndRoute: 'Oral via bell drinkers for 3 consecutive days',
      startDate: getTodayIso(-10),
      completionDate: getTodayIso(-7),
      personResponsible: 'Farm Operations Staff',
      vetOrSupplierAdvice: 'Recommended by Liwonde District Veterinary Officer',
      status: 'resolved',
      notes: 'Birds resumed active foraging and water intake restored to normal.',
      createdAt: Date.now() - 10 * 86400000,
    },
  ];

  const vaccinationEvents: VaccinationEvent[] = [
    {
      id: 'vac_01',
      flockId: 'flk_02',
      flockName: 'Sasso Growers Yard B',
      species: 'Sasso chicken',
      vaccineName: 'Gumboro IBD (Intermediate Strain)',
      targetDisease: 'Infectious Bursal Disease',
      scheduledDate: getTodayIso(-20),
      completedDate: getTodayIso(-20),
      status: 'completed',
      route: 'Drinking water with skimmed milk stabilizer',
      administeredBy: 'Isaac Chipeta',
      batchNo: 'IBD-MW-441',
      notes: 'Vaccination completed smoothly with zero adverse reactions.',
      createdAt: Date.now() - 20 * 86400000,
    },
    {
      id: 'vac_02',
      flockId: 'flk_01',
      flockName: 'Mikolongwe Layers Shed A',
      species: 'Mikolongwe chicken',
      vaccineName: 'Newcastle Disease (Lasota Booster)',
      targetDisease: 'Newcastle Disease',
      scheduledDate: getTodayIso(5),
      status: 'scheduled',
      route: 'Drinking water / Eye drop',
      administeredBy: 'Farm Operations Staff',
      notes: 'Quarterly routine booster due next week.',
      createdAt: Date.now() - 5 * 86400000,
    },
  ];

  const mortalityRecords: MortalityRecord[] = [
    {
      id: 'mort_01',
      date: getTodayIso(-10),
      flockId: 'flk_01',
      flockName: 'Mikolongwe Layers Shed A',
      numberOfBirds: 4,
      ageGroup: 'laying_hens',
      suspectedCause: 'Heat stress / smothering near corner wall',
      observedSymptoms: 'Birds congested in corner during hot spell, no signs of disease or discharge',
      actionTaken: 'Improved cross-ventilation, removed corner obstruction, added electrolyte water',
      recordedBy: 'Farm Operations Staff',
      notes: 'Post-mortem examination showed clear lungs and organs.',
      createdAt: Date.now() - 10 * 86400000,
    },
  ];

  const sales: Sale[] = [
    {
      id: 'sale_01',
      invoiceNumber: 'INV-2026-081',
      date: today,
      customerId: 'cust_03',
      customerName: 'Shire River Safari Lodge',
      customerPhone: '+265 999 876 543',
      customerAddress: 'Liwonde National Park Road',
      productCategory: 'fertilized_eggs',
      description: 'Fertilized Hatching Eggs (10 Trays of 30)',
      quantity: 10,
      unit: 'trays',
      unitPrice: 8500,
      discount: 0,
      total: 85000,
      paymentReceived: 85000,
      balanceDue: 0,
      paymentMethod: 'bank_transfer',
      deliveryStatus: 'delivered',
      driverOrDeliverer: 'Sikizwayo Chipeta (Motorbike Delivery)',
      salesperson: 'Sikizwayo Chipeta',
      notes: 'High fertility Mikolongwe & Sasso hatching eggs.',
      createdAt: Date.now() - 7200000,
    },
    {
      id: 'sale_02',
      invoiceNumber: 'INV-2026-082',
      date: today,
      customerName: 'Liwonde Local Bakery',
      customerPhone: '+265 882 110 099',
      customerAddress: 'Liwonde Market',
      productCategory: 'damaged_eggs',
      description: 'Damaged / Slightly Cracked Eggs for Baking',
      quantity: 5,
      unit: 'trays',
      unitPrice: 2500,
      discount: 0,
      total: 12500,
      paymentReceived: 12500,
      balanceDue: 0,
      paymentMethod: 'cash',
      deliveryStatus: 'collected',
      salesperson: 'Sikizwayo Chipeta',
      notes: 'Discounted cracked eggs sold for bakery consumption.',
      createdAt: Date.now() - 3600000,
    },
    {
      id: 'sale_03',
      invoiceNumber: 'INV-2026-083',
      date: getTodayIso(-1),
      customerName: 'Machinga Road Vendors',
      productCategory: 'infertile_eggs',
      description: 'Infertile / Clear Candled Eggs (Day 7 Candling)',
      quantity: 4,
      unit: 'trays',
      unitPrice: 3000,
      discount: 0,
      total: 12000,
      paymentReceived: 12000,
      balanceDue: 0,
      paymentMethod: 'cash',
      deliveryStatus: 'collected',
      salesperson: 'Sikizwayo Chipeta',
      notes: 'Candled clears removed from Incubator 1.',
      createdAt: Date.now() - 1 * 86400000,
    },
    {
      id: 'sale_04',
      invoiceNumber: 'INV-2026-084',
      date: getTodayIso(-2),
      customerId: 'cust_01',
      customerName: 'Hastings Phiri',
      customerPhone: '+265 991 223 344',
      productCategory: 'quails',
      description: 'Live Quals (Quails - 6 Weeks Old)',
      quantity: 20,
      unit: 'birds',
      unitPrice: 4000,
      discount: 5000,
      total: 75000,
      paymentReceived: 75000,
      balanceDue: 0,
      paymentMethod: 'airtel_money',
      deliveryStatus: 'collected',
      salesperson: 'Sikizwayo Chipeta',
      notes: 'Healthy breeding stock of quails.',
      createdAt: Date.now() - 2 * 86400000,
    },
    {
      id: 'sale_05',
      invoiceNumber: 'INV-2026-085',
      date: getTodayIso(-3),
      customerName: 'Chikwawa Waterfowl Farm',
      customerPhone: '+265 888 776 655',
      productCategory: 'ducklings',
      description: 'Day-Old Ducklings (DOC)',
      quantity: 10,
      unit: 'birds',
      unitPrice: 10000,
      discount: 0,
      total: 100000,
      paymentReceived: 70000,
      balanceDue: 30000,
      paymentMethod: 'tnm_mpamba',
      deliveryStatus: 'collected',
      salesperson: 'Sikizwayo Chipeta',
      notes: 'Balance of MK 30,000 on next delivery.',
      createdAt: Date.now() - 3 * 86400000,
    },
    {
      id: 'sale_06',
      invoiceNumber: 'INV-2026-086',
      date: getTodayIso(-4),
      customerName: 'Liwonde Safari Restaurant',
      customerPhone: '+265 994 556 677',
      productCategory: 'ducks',
      description: 'Mature Farm Ducks (Table Meat)',
      quantity: 4,
      unit: 'birds',
      unitPrice: 25000,
      discount: 0,
      total: 100000,
      paymentReceived: 100000,
      balanceDue: 0,
      paymentMethod: 'bank_transfer',
      deliveryStatus: 'delivered',
      driverOrDeliverer: 'Sikizwayo Chipeta',
      salesperson: 'Sikizwayo Chipeta',
      notes: 'Delivered dressed and chilled.',
      createdAt: Date.now() - 4 * 86400000,
    },
  ];

  const suppliers: Supplier[] = [
    {
      id: 'sup_01',
      name: 'Farmers World Liwonde',
      phone: '+265 888 123 456',
      location: 'Liwonde Trading Centre',
      productsSupplied: 'Poultry feeds, vitamins, disinfectants, drinkers and feeders',
      outstandingBalance: 0,
    },
    {
      id: 'sup_02',
      name: 'Zomba Veterinary Services',
      phone: '+265 999 654 321',
      location: 'Zomba City',
      productsSupplied: 'Vaccines, veterinary consultations, antibiotics',
      outstandingBalance: 0,
    },
    {
      id: 'sup_03',
      name: 'Southern Solar Power Systems',
      phone: '+265 881 990 011',
      location: 'Blantyre / Liwonde',
      productsSupplied: 'Solar inverter batteries for incubator continuous backup',
      outstandingBalance: 0,
    },
  ];

  const expenses: Expense[] = [
    {
      id: 'exp_01',
      expenseNumber: 'EXP-2026-052',
      date: getTodayIso(-5),
      category: 'feed',
      description: '10 Bags Farmers World Layer Mash (500kg)',
      amount: 385000,
      paymentMethod: 'bank_transfer',
      recipientOrPayee: 'Farmers World Liwonde',
      referenceNumber: 'FW-INV-9932',
      recordedBy: 'Isaac Chipeta',
      notes: 'Monthly bulk feed order for Shed 1.',
      createdAt: Date.now() - 5 * 86400000,
    },
    {
      id: 'exp_02',
      expenseNumber: 'EXP-2026-053',
      date: getTodayIso(-3),
      category: 'electricity',
      description: 'ESCOM Prepaid Electricity Token for Incubator House',
      amount: 45000,
      paymentMethod: 'tnm_mpamba',
      recipientOrPayee: 'ESCOM Prepaid',
      referenceNumber: 'ESC-MTR-8874102',
      recordedBy: 'Isaac Chipeta',
      notes: 'Units credited: 220 kWh.',
      createdAt: Date.now() - 3 * 86400000,
    },
    {
      id: 'exp_03',
      expenseNumber: 'EXP-2026-054',
      date: today,
      category: 'packaging',
      description: '50 heavy cardboard egg trays (30-egg size)',
      amount: 15000,
      paymentMethod: 'cash',
      recipientOrPayee: 'Liwonde Packaging Merchant',
      recordedBy: 'Sikizwayo Chipeta',
      notes: 'New trays for customer deliveries.',
      createdAt: Date.now() - 10000000,
    },
  ];

  const tasks: FarmTask[] = [
    {
      id: 'tsk_01',
      title: 'Morning egg collection & grading (All Sheds)',
      category: 'egg_collection',
      dueDate: today,
      dueTime: '08:00',
      assignedPerson: 'Farm Operations Staff',
      priority: 'high',
      status: 'completed',
      completedDate: today,
      notes: 'Recorded in egg collection register.',
      createdAt: Date.now() - 20000000,
    },
    {
      id: 'tsk_02',
      title: 'Check incubator temperature & water reservoirs (Solar Backup)',
      category: 'hatch_monitoring',
      dueDate: today,
      dueTime: '12:00',
      assignedPerson: 'Isaac Chipeta',
      priority: 'high',
      status: 'completed',
      completedDate: today,
      notes: 'Temp 37.5°C, humidity 56%, battery bank at 100% solar charge.',
      createdAt: Date.now() - 18000000,
    },
    {
      id: 'tsk_03',
      title: 'Prepare lockdown baskets for Batch INC-2026-002',
      category: 'incubator_transfer',
      dueDate: today,
      dueTime: '16:00',
      assignedPerson: 'Isaac Chipeta',
      priority: 'high',
      status: 'pending',
      notes: 'Local chicken batch due to hatch in 48 hours.',
      createdAt: Date.now() - 15000000,
    },
    {
      id: 'tsk_04',
      title: 'Follow up customer Mr. Patrick Phiri for collection prep',
      category: 'customer_collection',
      dueDate: getTodayIso(1),
      dueTime: '10:00',
      assignedPerson: 'Sikizwayo Chipeta',
      priority: 'medium',
      status: 'pending',
      notes: 'Turkey chicks entering hatching phase in 72 hours.',
      createdAt: Date.now() - 10000000,
    },
    {
      id: 'tsk_05',
      title: 'Clean and disinfect Brooder House C with Virkon-S',
      category: 'disinfection',
      dueDate: getTodayIso(2),
      dueTime: '09:00',
      assignedPerson: 'Farm Operations Staff',
      priority: 'medium',
      status: 'pending',
      notes: 'Prepare fresh litter for upcoming Mikolongwe chicks.',
      createdAt: Date.now() - 5000000,
    },
  ];

  const auditLogs: AuditLog[] = [
    {
      id: 'aud_01',
      timestamp: Date.now() - 86400000,
      date: getTodayIso(-1),
      user: 'Isaac Chipeta',
      role: 'managing_director',
      action: 'SYSTEM_INITIALIZATION',
      module: 'system',
      details: 'Msantha Poultry Farm database initialized with official farm records.',
    },
  ];

  return {
    version: 1,
    lastUpdated: Date.now(),
    settings: DEFAULT_SETTINGS,
    currentUserId: 'user_isaac',
    users: DEFAULT_USERS,
    isAuthenticated: true,
    dismissedAlertIds: [],
    language: 'en',
    flocks,
    movements,
    eggCollections,
    farmIncubations,
    customers,
    customerIncubations,
    feedItems,
    feedUsage,
    healthRecords,
    vaccinationEvents,
    mortalityRecords,
    sales,
    suppliers,
    expenses,
    tasks,
    auditLogs,
  };
}

// In-Memory Cached Database instance
let inMemoryDb: FarmDatabase | null = null;
const listeners = new Set<() => void>();

export function subscribeToDatabase(listener: () => void): () => void {
  listeners.add(listener);
  return () => {
    listeners.delete(listener);
  };
}

function notifySubscribers() {
  listeners.forEach((fn) => {
    try {
      fn();
    } catch (e) {
      console.error('Error notifying db subscriber', e);
    }
  });
}

export function getDatabase(): FarmDatabase {
  if (inMemoryDb) {
    return inMemoryDb;
  }

  // Load from localStorage
  try {
    const raw = localStorage.getItem(STORAGE_KEY);
    if (raw) {
      const parsed = JSON.parse(raw);
      if (parsed && parsed.version && parsed.settings) {
        // Upgrade users array if needed
        if (!Array.isArray(parsed.users) || parsed.users.length === 0) {
          parsed.users = DEFAULT_USERS;
        } else {
          parsed.users = parsed.users.map((u: any) => {
            const def = DEFAULT_USERS.find((d) => d.id === u.id);
            return {
              ...def,
              ...u,
              username: u.username || def?.username || u.name.toLowerCase().replace(/\s+/g, ''),
              pin: u.pin || def?.pin || '1234',
              isViewOnly: u.role === 'viewer' || u.isViewOnly === true,
            };
          });
        }

        if (parsed.isAuthenticated === undefined) {
          parsed.isAuthenticated = true;
        }
        if (!Array.isArray(parsed.dismissedAlertIds)) {
          parsed.dismissedAlertIds = [];
        }

        if (!parsed.settings.speciesIncubationDays) {
          parsed.settings.speciesIncubationDays = DEFAULT_SETTINGS.speciesIncubationDays;
        } else if (!parsed.settings.speciesIncubationDays['Guinea fowl']) {
          parsed.settings.speciesIncubationDays['Guinea fowl'] = 28;
        }

        inMemoryDb = parsed;
        return inMemoryDb!;
      }
    }
  } catch (err) {
    console.warn('Could not read localStorage database, falling back to seed data', err);
  }

  // Seed default data
  const initial = createInitialSeedData();
  saveDatabase(initial);
  return initial;
}

export function saveDatabase(db: FarmDatabase): void {
  db.lastUpdated = Date.now();
  inMemoryDb = db;
  try {
    localStorage.setItem(STORAGE_KEY, JSON.stringify(db));
  } catch (err) {
    console.error('Failed to save to localStorage', err);
  }
  notifySubscribers();
}

export function recordAudit(
  user: string,
  role: UserProfile['role'],
  action: string,
  module: string,
  details: string
): void {
  const db = getDatabase();
  const newLog: AuditLog = {
    id: `aud_${Date.now()}_${Math.random().toString(36).substring(2, 6)}`,
    timestamp: Date.now(),
    date: new Date().toISOString().split('T')[0],
    user,
    role,
    action,
    module,
    details,
  };
  db.auditLogs.unshift(newLog);
  // Keep maximum 500 audit logs to save space
  if (db.auditLogs.length > 500) {
    db.auditLogs = db.auditLogs.slice(0, 500);
  }
  saveDatabase(db);
}

// Export database as JSON file for offline backup
export function exportDatabaseBackup(): void {
  const db = getDatabase();
  const json = JSON.stringify(db, null, 2);
  const blob = new Blob([json], { type: 'application/json' });
  const url = URL.createObjectURL(blob);
  const dateStr = new Date().toISOString().split('T')[0];
  const a = document.createElement('a');
  a.href = url;
  a.download = `msantha_poultry_farm_backup_${dateStr}.json`;
  document.body.appendChild(a);
  a.click();
  document.body.removeChild(a);
  URL.revokeObjectURL(url);
}

// Restore database from JSON file
export function restoreDatabaseBackup(jsonData: string): { success: boolean; message: string } {
  try {
    const parsed = JSON.parse(jsonData);
    if (!parsed || !parsed.settings || !Array.isArray(parsed.flocks)) {
      return { success: false, message: 'Invalid backup file format.' };
    }

    parsed.version = 1;
    parsed.lastUpdated = Date.now();
    saveDatabase(parsed);
    return { success: true, message: 'Farm records restored successfully.' };
  } catch (err) {
    return { success: false, message: `Failed to restore backup: ${(err as Error).message}` };
  }
}

// Reset data to default seed
export function resetToSeedData(): void {
  const initial = createInitialSeedData();
  saveDatabase(initial);
}

// Completely clear all farm records (fresh empty state)
export function clearAllFarmData(): void {
  const empty: FarmDatabase = {
    version: 1,
    lastUpdated: Date.now(),
    settings: DEFAULT_SETTINGS,
    currentUserId: 'user_isaac',
    language: 'en',
    flocks: [],
    movements: [],
    eggCollections: [],
    farmIncubations: [],
    customers: [],
    customerIncubations: [],
    feedItems: [],
    feedUsage: [],
    healthRecords: [],
    vaccinationEvents: [],
    mortalityRecords: [],
    sales: [],
    suppliers: [],
    expenses: [],
    tasks: [],
    auditLogs: [],
    users: DEFAULT_USERS,
    isAuthenticated: true,
    dismissedAlertIds: [],
  };
  saveDatabase(empty);
}
