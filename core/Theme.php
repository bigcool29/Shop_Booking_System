<?php
/**
 * System Theme & Appearance Manager
 * Provides curated modern themes for CMTC Online Shop & Admin Panel
 */

require_once __DIR__ . '/../config/settings.php';

class Theme {
    const DEFAULT_THEME = 'classic_blue';

    /**
     * Get all available theme definitions
     */
    public static function getAll() {
        return [
            'classic_blue' => [
                'id' => 'classic_blue',
                'name' => 'CMTC Classic Navy',
                'name_th' => 'กรมท่า-น้ำเงินมาตรฐาน (วิทยาลัยเทคนิคเชียงใหม่)',
                'tag' => 'Official CMTC',
                'badge' => 'ค่าเริ่มต้นทางการ',
                'badge_color' => 'bg-blue-100 text-blue-800 border-blue-200',
                'description' => 'โทนสีน้ำเงินกรมท่าเป็นทางการ สง่างาม มั่นคง น่าเชื่อถือ ตามเอกลักษณ์ของวิทยาลัยเทคนิคเชียงใหม่',
                'accent' => '#f59e0b',
                'swatches' => ['#1e3a8a', '#2563eb', '#60a5fa', '#f59e0b'],
                'gradient' => 'linear-gradient(135deg, #1e3a8a 0%, #2563eb 50%, #1d4ed8 100%)',
                'sidebar_bg' => '#0f172a',
                'colors' => [
                    '50' => '#eff6ff',
                    '100' => '#dbeafe',
                    '200' => '#bfdbfe',
                    '300' => '#93c5fd',
                    '400' => '#60a5fa',
                    '500' => '#3b82f6',
                    '600' => '#2563eb',
                    '700' => '#1d4ed8',
                    '800' => '#1e40af',
                    '900' => '#1e3a8a',
                    '950' => '#172554'
                ]
            ],
            'emerald_mint' => [
                'id' => 'emerald_mint',
                'name' => 'Emerald & Mint',
                'name_th' => 'เขียวมรกต-มิ้นท์ทันสมัย (Eco & Smart Campus)',
                'tag' => 'Eco & Smart',
                'badge' => 'สดชื่น & สบายตา',
                'badge_color' => 'bg-emerald-100 text-emerald-800 border-emerald-200',
                'description' => 'โทนสีเขียวมรกตและมิ้นท์ สดชื่น สะอาดตา ทันสมัย สื่อถึงพลังสร้างสรรค์และความยั่งยืน',
                'accent' => '#14b8a6',
                'swatches' => ['#064e3b', '#059669', '#34d399', '#14b8a6'],
                'gradient' => 'linear-gradient(135deg, #064e3b 0%, #059669 50%, #10b981 100%)',
                'sidebar_bg' => '#062a22',
                'colors' => [
                    '50' => '#ecfdf5',
                    '100' => '#d1fae5',
                    '200' => '#a7f3d0',
                    '300' => '#6ee7b7',
                    '400' => '#34d399',
                    '500' => '#10b981',
                    '600' => '#059669',
                    '700' => '#047857',
                    '800' => '#065f46',
                    '900' => '#064e3b',
                    '950' => '#022c22'
                ]
            ],
            'ruby_crimson' => [
                'id' => 'ruby_crimson',
                'name' => 'Ruby & Maroon',
                'name_th' => 'แดงทับทิม-เลือดหมูทรงพลัง (วิศวกรรม & ช่างอุตสาหกรรม)',
                'tag' => 'Engineering Pride',
                'badge' => 'ทรงพลัง & โดดเด่น',
                'badge_color' => 'bg-rose-100 text-rose-800 border-rose-200',
                'description' => 'โทนสีแดงทับทิมและเลือดหมู ทรงพลัง เข้มแข็ง สง่างาม สไตล์วิศวกรรมและสายช่างอุตสาหกรรม',
                'accent' => '#f59e0b',
                'swatches' => ['#881337', '#e11d48', '#fb7185', '#f59e0b'],
                'gradient' => 'linear-gradient(135deg, #881337 0%, #e11d48 50%, #be123c 100%)',
                'sidebar_bg' => '#1f040b',
                'colors' => [
                    '50' => '#fff1f2',
                    '100' => '#ffe4e6',
                    '200' => '#fecdd3',
                    '300' => '#fda4af',
                    '400' => '#fb7185',
                    '500' => '#f43f5e',
                    '600' => '#e11d48',
                    '700' => '#be123c',
                    '800' => '#9f1239',
                    '900' => '#881337',
                    '950' => '#4c0519'
                ]
            ],
            'royal_purple' => [
                'id' => 'royal_purple',
                'name' => 'Royal Violet & Indigo',
                'name_th' => 'ม่วงคราม-ดิจิทัลอินโนเวชัน (Creative & Innovation)',
                'tag' => 'Tech & Creative',
                'badge' => 'ล้ำสมัย & ไฮเทค',
                'badge_color' => 'bg-purple-100 text-purple-800 border-purple-200',
                'description' => 'โทนสีม่วงรอยัลและคราม สื่อถึงเทคโนโลยี นวัตกรรม ความคิดสร้างสรรค์ และโลกดิจิทัลยุคใหม่',
                'accent' => '#ec4899',
                'swatches' => ['#4c1d95', '#7c3aed', '#a78bfa', '#ec4899'],
                'gradient' => 'linear-gradient(135deg, #4c1d95 0%, #7c3aed 50%, #6d28d9 100%)',
                'sidebar_bg' => '#13082a',
                'colors' => [
                    '50' => '#f5f3ff',
                    '100' => '#ede9fe',
                    '200' => '#ddd6fe',
                    '300' => '#c4b5fd',
                    '400' => '#a78bfa',
                    '500' => '#8b5cf6',
                    '600' => '#7c3aed',
                    '700' => '#6d28d9',
                    '800' => '#5b21b6',
                    '900' => '#4c1d95',
                    '950' => '#2e1065'
                ]
            ],
            'sunset_amber' => [
                'id' => 'sunset_amber',
                'name' => 'Sunset Amber & Coral',
                'name_th' => 'ส้มอำพัน-คอรัลมีชีวิตชีวา (Energy & Dynamic)',
                'tag' => 'Warm & Dynamic',
                'badge' => 'มีพลัง & อบอุ่น',
                'badge_color' => 'bg-amber-100 text-amber-800 border-amber-200',
                'description' => 'โทนสีส้มอำพันและคอรัล กระตือรือร้น มีชีวิตชีวา เป็นมิตร และสร้างความตื่นเต้นในการสั่งจองสินค้า',
                'accent' => '#eab308',
                'swatches' => ['#7c2d12', '#ea580c', '#fb923c', '#eab308'],
                'gradient' => 'linear-gradient(135deg, #7c2d12 0%, #ea580c 50%, #c2410c 100%)',
                'sidebar_bg' => '#1f0b04',
                'colors' => [
                    '50' => '#fff7ed',
                    '100' => '#ffedd5',
                    '200' => '#fed7aa',
                    '300' => '#fdba74',
                    '400' => '#fb923c',
                    '500' => '#f97316',
                    '600' => '#ea580c',
                    '700' => '#c2410c',
                    '800' => '#9a3412',
                    '900' => '#7c2d12',
                    '950' => '#431407'
                ]
            ],
            'midnight_dark' => [
                'id' => 'midnight_dark',
                'name' => 'Midnight Cyber Dark',
                'name_th' => 'ดาร์กสเลท-ไซเบอร์บลูพรีเมียม (Cyberpunk & Luxury Dark)',
                'tag' => 'Luxury Dark Mode',
                'badge' => 'พรีเมียม & ทันสมัย',
                'badge_color' => 'bg-sky-100 text-sky-800 border-sky-200',
                'description' => 'โทนสีน้ำเงินไซเบอร์และดำสเลท สไตล์หรูหรา ลุ่มลึก ล้ำสมัย และให้ความคมชัดสูงสุดบนหน้าจอ',
                'accent' => '#06b6d4',
                'swatches' => ['#090d16', '#0284c7', '#38bdf8', '#06b6d4'],
                'gradient' => 'linear-gradient(135deg, #090d16 0%, #0c4a6e 50%, #0284c7 100%)',
                'sidebar_bg' => '#090d16',
                'colors' => [
                    '50' => '#f0f9ff',
                    '100' => '#e0f2fe',
                    '200' => '#bae6fd',
                    '300' => '#7dd3fc',
                    '400' => '#38bdf8',
                    '500' => '#0ea5e9',
                    '600' => '#0284c7',
                    '700' => '#0369a1',
                    '800' => '#075985',
                    '900' => '#0c4a6e',
                    '950' => '#082f49'
                ]
            ]
        ];
    }

    /**
     * Get currently active theme key
     */
    public static function getCurrentKey() {
        return get_setting('system_theme', self::DEFAULT_THEME);
    }

    /**
     * Get theme data by ID
     */
    public static function get($key) {
        $themes = self::getAll();
        return $themes[$key] ?? $themes[self::DEFAULT_THEME];
    }

    /**
     * Get currently active theme data
     */
    public static function getCurrent() {
        return self::get(self::getCurrentKey());
    }

    /**
     * Output Tailwind config script & CSS variables into <head>
     */
    public static function renderHead() {
        $theme = self::getCurrent();
        $colorsJson = json_encode($theme['colors'], JSON_UNESCAPED_SLASHES);
        $accent = $theme['accent'];
        $gradient = $theme['gradient'];
        $p600 = $theme['colors']['600'];
        $p700 = $theme['colors']['700'];
        $p500 = $theme['colors']['500'];
        $p50 = $theme['colors']['50'];
        $p100 = $theme['colors']['100'];

        return <<<HTML
    <!-- Dynamic Theme Engine: {$theme['name']} -->
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        blue: {$colorsJson},
                        brand: {$colorsJson}
                    }
                }
            }
        };
    </script>
    <style>
        :root {
            --primary-color: {$p600};
            --primary-light: {$p500};
            --primary-dark: {$p700};
            --primary-50: {$p50};
            --primary-100: {$p100};
            --accent-color: {$accent};
            --theme-gradient: {$gradient};
        }
        .theme-gradient-bg {
            background: {$gradient} !important;
        }
        .theme-gradient-text {
            background: {$gradient};
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .theme-text-primary {
            color: {$p600} !important;
        }
        .theme-bg-primary {
            background-color: {$p600} !important;
        }
        .theme-border-primary {
            border-color: {$p600} !important;
        }
    </style>
HTML;
    }
}
