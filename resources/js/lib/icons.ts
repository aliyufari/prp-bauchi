import {
  LayoutDashboard, Users, UserCheck, Briefcase, Handshake, RotateCcw, Shield, Vote,
  Calendar, MapPin, Users2, BarChart3, Activity, Megaphone, BookOpen, Baby, DoorClosed,
  UserCog, CreditCard, Receipt, HandCoins, FileText, ClipboardList, Bot, TrendingUp,
  MessageSquare, Star, Settings, Key, ScrollText, HardDrive, FileBarChart, Home, User,
} from 'lucide-vue-next'

const icons = {
  LayoutDashboard, Users, UserCheck, Briefcase, Handshake, RotateCcw, Shield, Vote,
  Calendar, MapPin, Users2, BarChart3, Activity, Megaphone, BookOpen, Baby, DoorClosed,
  UserCog, CreditCard, Receipt, HandCoins, FileText, ClipboardList, Bot, TrendingUp,
  MessageSquare, Star, Settings, Key, ScrollText, HardDrive, FileBarChart, Home, User,
} as const

export const resolveIcon = (name?: string) => (name && (icons as Record<string, unknown>)[name]) || Users