import { BoxIcon, HeadphonesIcon, HomeIcon, LaptopIcon, PhoneIcon, WatchIcon } from './icons'

// Danh mục do admin tạo nên không thể biết trước: danh mục lạ dùng icon hộp mặc định
const ICONS = {
  'dien-thoai': PhoneIcon,
  laptop: LaptopIcon,
  'phu-kien': HeadphonesIcon,
  'dong-ho': WatchIcon,
  'gia-dung': HomeIcon,
}

export default function CategoryIcon({ slug, size = 28 }) {
  const Icon = ICONS[slug] ?? BoxIcon
  return <Icon size={size} />
}
