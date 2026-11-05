/**
 * Msantha Poultry Farm Manager - Type Definitions
 * Location: Liwonde, Malawi
 */

export type UserRole = 'managing_director' | 'marketing_manager' | 'farm_worker' | 'viewer';

export interface UserProfile {
  id: string;
  name: string;
  username: string; // for login
  pin: string; // 4-digit PIN for farm login & security
  role: UserRole;
  title: string;
  phone?: string;
  avatarColor: string;
  isViewOnly?: boolean;
}

export type AlertSeverity = 'critical' | 'warning' | 'info';

export interface FarmAlert {
  id: string;
  title: string;
  description: string;
  category: 'incubation' | 'feed' | 'health' | 'tasks' | 'finance' | 'stock' | 'general';
  severity: AlertSeverity;
  timestamp: number;
  actionLabel?: string;
  actionTab?: string;
  relatedEntityId?: string;
}

export type Language = 'en' | 'ny'; // English or Chichewa (Nyanja)

export interface FarmSettings {
  farmName: string;
  location: string;
  managingDirector: string;
  marketingManager: string;
  currency: string;
  currencySymbol: string;
  defaultTraySize: number; // usually 30 eggs
  lowFeedThresholdKg: number;
  lowEggStockAlertTrays: number;
  phoneContact: string;
  emailContact: string;
  speciesIncubationDays: Record<string, number>;
  trayPriceMwk: number;
  eggUnitPriceMwk: number;
}

export type PoultrySpecies =
  | 'Mikolongwe chicken'
  | 'Sasso chicken'
  | 'Local chicken'
  | 'Turkey'
  | 'Duck'
  | 'Quail'
  | 'Guinea fowl'
  | 'Other';

export type PoultryPurpose = 'meat' | 'eggs' | 'breeding' | 'replacement' | 'dual_purpose';

export type AgeGroup =
  | 'day_old'
  | 'chicks'
  | 'growers'
  | 'pullets'
  | 'cockerels'
  | 'laying_hens'
  | 'non_laying_hens'
  | 'breeding_hens'
  | 'adult_cocks'
  | 'turkey_poults'
  | 'adult_turkeys'
  | 'ducklings'
  | 'adult_ducks'
  | 'quail_chicks'
  | 'adult_quails'
  | 'keets'
  | 'adult_guinea_fowls'
  | 'other';

export interface FlockMovement {
  id: string;
  flockId: string;
  date: string; // YYYY-MM-DD
  type: 'initial' | 'added' | 'transferred_in' | 'transferred_out' | 'sold' | 'culled' | 'mortality' | 'stock_adjustment';
  quantity: number;
  previousQuantity: number;
  newQuantity: number;
  destinationShed?: string;
  reason?: string;
  recordedBy: string;
  timestamp: number;
}

export interface Flock {
  id: string;
  flockCode: string;
  name: string;
  species: PoultrySpecies;
  breed: string;
  dateAcquired: string; // YYYY-MM-DD
  sourceSupplier: string;
  initialQuantity: number;
  currentQuantity: number;
  housingShed: string;
  purpose: PoultryPurpose;
  ageGroup: AgeGroup;
  status: 'active' | 'sold' | 'culled' | 'completed';
  notes?: string;
  createdAt: number;
}

export interface EggCollection {
  id: string;
  collectionDate: string; // YYYY-MM-DD
  collectionTime: string; // HH:mm
  flockId: string;
  flockName: string;
  species: PoultrySpecies;
  breed: string;
  totalCollected: number;
  saleableCount: number;
  crackedCount: number;
  dirtyCount: number;
  damagedRejectedCount: number;
  farmUsedCount: number;
  reservedIncubationCount: number;
  recordedBy: string;
  notes?: string;
  createdAt: number;
}

export type IncubationStage =
  | 'eggs_prepared'
  | 'incubating'
  | 'first_candling_due'
  | 'candled'
  | 'lockdown'
  | 'hatching'
  | 'completed';

export interface FarmIncubationBatch {
  id: string;
  batchCode: string;
  species: PoultrySpecies;
  breedSource: string;
  flockId?: string;
  eggSettingDate: string; // YYYY-MM-DD
  eggsSet: number;
  incubatorId: string;
  expectedHatchDate: string; // YYYY-MM-DD
  incubationDurationDays: number;
  firstCandlingDate: string;
  firstCandlingResult?: {
    fertileCount: number;
    infertileCount: number;
    dateRecorded: string;
  };
  secondCandlingDate?: string;
  secondCandlingResult?: {
    viableCount: number;
    deadInShellCount: number;
    dateRecorded: string;
  };
  lockdownDate: string;
  actualHatchDate?: string;
  chicksHatched?: number;
  unhatchedEggs?: number;
  hatchPercentage?: number;
  chickQualityNotes?: string;
  status: IncubationStage;
  notes?: string;
  createdAt: number;
}

export interface Customer {
  id: string;
  customerCode: string;
  name: string;
  phone: string;
  addressVillage: string;
  category: 'individual' | 'commercial_farmer' | 'vendor' | 'restaurant' | 'wholesale';
  notes?: string;
  createdAt: number;
}

export type PaymentMethod = 'cash' | 'airtel_money' | 'tnm_mpamba' | 'bank_transfer' | 'other';

export interface PaymentRecord {
  id: string;
  date: string; // YYYY-MM-DD
  amount: number;
  method: PaymentMethod;
  receiptNumber: string;
  recordedBy: string;
  notes?: string;
  timestamp: number;
}

export interface CustomerIncubationBatch {
  id: string;
  batchCode: string;
  customerId: string;
  customerName: string;
  customerPhone: string;
  customerVillage?: string;
  species: PoultrySpecies;
  breed?: string;
  dateReceived: string; // YYYY-MM-DD
  dateSet: string; // YYYY-MM-DD
  eggsReceived: number;
  eggsAccepted: number;
  eggsRejectedAtIntake: number;
  rejectionReason?: string;
  incubatorId: string;
  durationDays: number;
  expectedHatchDate: string;
  plannedCollectionDate: string;
  actualCollectionDate?: string;
  fertileEggsCount?: number;
  chicksHatched?: number;
  chicksDelivered?: number;
  unhatchedEggs?: number;
  status: 'received' | 'incubating' | 'candled' | 'lockdown' | 'hatched' | 'collected' | 'cancelled';
  pricingMethod: 'per_egg' | 'per_chick' | 'flat_fee' | 'custom';
  unitRate: number; // MWK per egg/chick
  totalCharge: number;
  deposit: number;
  amountPaid: number;
  balanceDue: number;
  payments: PaymentRecord[];
  isCollected: boolean;
  notes?: string;
  recordedBy: string;
  createdAt: number;
}

export type FeedType =
  | 'chick_starter'
  | 'grower_feed'
  | 'layer_feed'
  | 'broiler_starter'
  | 'broiler_grower'
  | 'broiler_finisher'
  | 'breeder_feed'
  | 'other';

export interface FeedItem {
  id: string;
  productName: string;
  feedType: FeedType;
  supplier: string;
  bagSizeKg: number;
  costPerBag: number;
  currentStockKg: number;
  lowStockAlertKg: number;
  storageLocation: string;
  lastRestockedDate?: string;
}

export interface FeedUsage {
  id: string;
  date: string;
  flockId: string;
  flockName: string;
  feedItemId: string;
  productName: string;
  quantityUsedKg: number;
  recordedBy: string;
  notes?: string;
  createdAt: number;
}

export interface HealthRecord {
  id: string;
  date: string; // YYYY-MM-DD
  flockId: string;
  flockName: string;
  healthIssue: string;
  symptomsObserved: string;
  medicineOrVaccine: string;
  batchNumber?: string;
  expiryDate?: string;
  quantityAdministered: string;
  dosageAndRoute: string; // e.g. "Oral in drinking water, 5ml per 10L"
  administrationDetails?: string;
  startDate: string;
  completionDate?: string;
  personResponsible: string;
  vetOrSupplierAdvice?: string;
  followUpDate?: string;
  status: 'ongoing' | 'resolved' | 'monitoring';
  notes?: string;
  createdAt: number;
}

export interface VaccinationEvent {
  id: string;
  flockId: string;
  flockName: string;
  species: PoultrySpecies;
  vaccineName: string; // e.g. "Newcastle (Lasota)", "Gumboro (IBD)", "Fowl Pox"
  targetDisease: string;
  scheduledDate: string;
  completedDate?: string;
  status: 'scheduled' | 'completed' | 'missed' | 'postponed';
  route: string;
  administeredBy?: string;
  batchNo?: string;
  notes?: string;
  createdAt: number;
}

export interface MortalityRecord {
  id: string;
  date: string;
  flockId: string;
  flockName: string;
  numberOfBirds: number;
  ageGroup: AgeGroup;
  suspectedCause: string; // "Unknown", "Heat stress", "Predator", etc.
  observedSymptoms?: string;
  actionTaken: string;
  recordedBy: string;
  notes?: string;
  createdAt: number;
}

export type ProductCategory =
  | 'fertilized_eggs' // replaces fresh eggs
  | 'damaged_eggs'    // cracked / culled eggs for bakery & cooking
  | 'infertile_eggs'  // candled clear eggs removed day 7-10
  | 'quails'          // Quals (live quails)
  | 'ducklings'       // day-old or young ducklings
  | 'ducks'           // mature / grower ducks
  | 'live_birds'      // live chickens (Mikolongwe, Sasso, Local)
  | 'day_old_chicks'  // DOCs
  | 'guinea_fowl'     // Guinea fowl keets / mature
  | 'turkeys'         // Turkeys / poults
  | 'growers_pullets'
  | 'spent_hens'
  | 'incubation_service'
  | 'manure_other'
  | 'eggs';           // legacy alias for backward compatibility

export interface Sale {
  id: string;
  invoiceNumber: string;
  date: string;
  customerId?: string;
  customerName: string;
  customerPhone?: string;
  customerAddress?: string;
  productCategory: ProductCategory;
  description: string;
  quantity: number;
  unit: 'individual_eggs' | 'trays' | 'cartons' | 'birds' | 'service' | 'bags' | 'kg';
  unitPrice: number; // MWK
  discount: number;
  total: number;
  paymentReceived: number;
  balanceDue: number;
  paymentMethod: PaymentMethod;
  deliveryStatus: 'collected' | 'pending_delivery' | 'dispatched' | 'delivered';
  driverOrDeliverer?: string;
  deliveryCost?: number;
  salesperson: string;
  relatedFlockId?: string;
  notes?: string;
  createdAt: number;
}

export interface Supplier {
  id: string;
  name: string;
  phone: string;
  location: string;
  productsSupplied: string;
  notes?: string;
  outstandingBalance: number;
}

export type ExpenseCategory =
  | 'feed'
  | 'chicks_poultry'
  | 'vaccines_medicines'
  | 'labour_salaries'
  | 'transport_fuel'
  | 'electricity'
  | 'water'
  | 'packaging'
  | 'equipment_repairs'
  | 'rent_overheads'
  | 'other';

export interface Expense {
  id: string;
  expenseNumber: string;
  date: string;
  category: ExpenseCategory;
  description: string;
  amount: number; // MWK
  paymentMethod: PaymentMethod;
  recipientOrPayee: string;
  referenceNumber?: string;
  relatedFlockId?: string;
  recordedBy: string;
  notes?: string;
  createdAt: number;
}

export type TaskCategory =
  | 'feeding'
  | 'egg_collection'
  | 'cleaning'
  | 'disinfection'
  | 'vaccination'
  | 'medication'
  | 'candling'
  | 'incubator_transfer'
  | 'hatch_monitoring'
  | 'customer_collection'
  | 'stock_check'
  | 'equipment_maintenance'
  | 'other';

export interface FarmTask {
  id: string;
  title: string;
  category: TaskCategory;
  dueDate: string; // YYYY-MM-DD
  dueTime?: string;
  assignedPerson: string;
  relatedEntityId?: string;
  relatedEntityType?: 'flock' | 'incubator' | 'customer_batch';
  priority: 'high' | 'medium' | 'low';
  status: 'pending' | 'in_progress' | 'completed' | 'overdue';
  completedDate?: string;
  notes?: string;
  createdAt: number;
}

export interface AuditLog {
  id: string;
  timestamp: number;
  date: string;
  user: string;
  role: UserRole;
  action: string;
  module: string;
  details: string;
}
