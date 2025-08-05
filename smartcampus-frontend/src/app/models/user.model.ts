export interface User {
  id?: number;
  email: string;
  nom: string;
  prenom: string;
  roles: string[];
  status: string;
  dateCreation?: string;
  classe?: Classe | null;
  parent?: User | null;
  enfants?: User[];
  presences?: Presence[];
  notes?: Note[];
}

export interface Classe {
  id: number;
  nom: string;
  niveau: string;
  description?: string;
  effectif?: number;
  enseignant?: User | null;
  etudiants?: User[];
  seances?: Seance[];
}

export interface Seance {
  id?: number;
  titre: string;
  description: string;
  dateDebut: string;
  dateFin: string;
  mode: string;
  lienVisio?: string;
  enseignant?: User;
  classe?: Classe;
  matiere?: Matiere;
  presences?: Presence[];
  notes?: Note[];
}

export interface Matiere {
  id?: number;
  nom: string;
  code: string;
  coefficient: number;
  seances?: Seance[];
}

export interface Presence {
  id?: number;
  status: string;
  dateMarquage?: string;
  commentaire?: string;
  etudiant?: User;
  seance?: Seance;
}

export interface Note {
  id?: number;
  valeur: number;
  coefficient: number;
  commentaire?: string;
  dateAttribution?: string;
  etudiant?: User;
  seance?: Seance;
}

export interface Message {
  id?: number;
  sujet: string;
  contenu: string;
  dateEnvoi?: string;
  lu: boolean;
  expediteur?: User;
  destinataire?: User;
  conversation?: Conversation;
}

export interface Conversation {
  id?: number;
  titre: string;
  dateCreation?: string;
  active: boolean;
  participants?: User[];
  messages?: Message[];
}

export interface Notification {
  id?: number;
  titre: string;
  contenu: string;
  dateCreation?: string;
  lu: boolean;
  priorite: string;
  user?: User;
}

export interface Calendrier {
  id?: number;
  semaine: number;
  annee: number;
  classe?: Classe;
  seances?: Seance[];
  evenements?: Evenement[];
}

export interface Evenement {
  id?: number;
  titre: string;
  description: string;
  dateDebut: string;
  dateFin: string;
  type: string;
  calendrier?: Calendrier;
  seance?: Seance;
}

export interface AuthResponse {
  token: string;
  user: User;
}

export interface LoginRequest {
  email: string;
  password: string;
}

export interface RegisterRequest {
  email: string;
  password: string;
  nom: string;
  prenom: string;
  roles: string[];
  classeId?: number;
  parentId?: number;
}

export interface ApiResponse<T> {
  message?: string;
  data?: T;
  error?: string;
  errors?: string[];
}

export enum UserRole {
  ADMIN = 'ROLE_ADMIN',
  TEACHER = 'ROLE_TEACHER',
  STUDENT = 'ROLE_STUDENT',
  PARENT = 'ROLE_PARENT'
}

export enum UserStatus {
  ACTIVE = 'active',
  INACTIVE = 'inactive',
  SUSPENDED = 'suspended'
}

export enum SeanceMode {
  PRESENTIEL = 'presentiel',
  DISTANCIEL = 'distanciel',
  HYBRIDE = 'hybride'
}

export enum PresenceStatus {
  PRESENT = 'present',
  ABSENT = 'absent',
  ABSENT_JUSTIFIE = 'absent_justifie',
  RETARD = 'retard'
}

export enum NotificationPriority {
  LOW = 'low',
  NORMAL = 'normal',
  HIGH = 'high',
  URGENT = 'urgent'
}