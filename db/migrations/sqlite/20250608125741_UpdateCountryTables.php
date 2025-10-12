<?php declare(strict_types=1);

use Phinx\Migration\AbstractMigration;

final class UpdateCountryTables extends AbstractMigration
{
    public function down() : void
    {
        $this->execute(
            <<<SQL
            CREATE TABLE `cache_tmdb_countries` (
                `iso_3166_1` TEXT,
                `english_name` TEXT NOT NULL,
                PRIMARY KEY (`iso_3166_1`)
            )
            SQL,
        );

        $this->execute('DROP TABLE `country`');
    }

    public function up() : void
    {
        $this->execute('DROP TABLE `cache_tmdb_countries`');

        $this->execute(
            <<<SQL
            CREATE TABLE `country` (
                `iso_3166_1` TEXT NOT NULL,
                `english_name` TEXT NOT NULL,
                `created_at` TEXT NOT NULL,
                `updated_at` TEXT DEFAULT NULL,
                PRIMARY KEY (`iso_3166_1`)
            )
            SQL,
        );

        $formattedDate = (new \DateTime())->format('Y-m-d H:i:s');
        $this->execute(
            <<<SQL
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AD', 'Andorra', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AE', 'United Arab Emirates', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AF', 'Afghanistan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AG', 'Antigua and Barbuda', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AI', 'Anguilla', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AL', 'Albania', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AM', 'Armenia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AN', 'Netherlands Antilles', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AO', 'Angola', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AQ', 'Antarctica', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AR', 'Argentina', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AS', 'American Samoa', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AT', 'Austria', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AU', 'Australia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AW', 'Aruba', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('AZ', 'Azerbaijan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BA', 'Bosnia and Herzegovina', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BB', 'Barbados', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BD', 'Bangladesh', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BE', 'Belgium', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BF', 'Burkina Faso', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BG', 'Bulgaria', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BH', 'Bahrain', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BI', 'Burundi', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BJ', 'Benin', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BM', 'Bermuda', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BN', 'Brunei Darussalam', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BO', 'Bolivia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BR', 'Brazil', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BS', 'Bahamas', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BT', 'Bhutan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BU', 'Burma', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BV', 'Bouvet Island', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BW', 'Botswana', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BY', 'Belarus', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('BZ', 'Belize', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CA', 'Canada', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CC', 'Cocos  Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CD', 'Congo', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CF', 'Central African Republic', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CG', 'Congo', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CH', 'Switzerland', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CI', 'Cote D''Ivoire', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CK', 'Cook Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CL', 'Chile', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CM', 'Cameroon', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CN', 'China', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CO', 'Colombia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CR', 'Costa Rica', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CS', 'Serbia and Montenegro', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CU', 'Cuba', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CV', 'Cape Verde', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CX', 'Christmas Island', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CY', 'Cyprus', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('CZ', 'Czech Republic', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('DE', 'Germany', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('DJ', 'Djibouti', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('DK', 'Denmark', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('DM', 'Dominica', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('DO', 'Dominican Republic', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('DZ', 'Algeria', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('EC', 'Ecuador', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('EE', 'Estonia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('EG', 'Egypt', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('EH', 'Western Sahara', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ER', 'Eritrea', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ES', 'Spain', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ET', 'Ethiopia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('FI', 'Finland', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('FJ', 'Fiji', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('FK', 'Falkland Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('FM', 'Micronesia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('FO', 'Faeroe Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('FR', 'France', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GA', 'Gabon', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GB', 'United Kingdom', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GD', 'Grenada', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GE', 'Georgia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GF', 'French Guiana', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GH', 'Ghana', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GI', 'Gibraltar', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GL', 'Greenland', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GM', 'Gambia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GN', 'Guinea', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GP', 'Guadaloupe', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GQ', 'Equatorial Guinea', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GR', 'Greece', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GS', 'South Georgia and the South Sandwich Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GT', 'Guatemala', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GU', 'Guam', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GW', 'Guinea-Bissau', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('GY', 'Guyana', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('HK', 'Hong Kong', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('HM', 'Heard and McDonald Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('HN', 'Honduras', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('HR', 'Croatia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('HT', 'Haiti', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('HU', 'Hungary', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ID', 'Indonesia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('IE', 'Ireland', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('IL', 'Israel', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('IN', 'India', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('IO', 'British Indian Ocean Territory', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('IQ', 'Iraq', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('IR', 'Iran', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('IS', 'Iceland', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('IT', 'Italy', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('JM', 'Jamaica', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('JO', 'Jordan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('JP', 'Japan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KE', 'Kenya', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KG', 'Kyrgyz Republic', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KH', 'Cambodia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KI', 'Kiribati', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KM', 'Comoros', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KN', 'St. Kitts and Nevis', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KP', 'North Korea', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KR', 'South Korea', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KW', 'Kuwait', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KY', 'Cayman Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('KZ', 'Kazakhstan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LA', 'Lao People''s Democratic Republic', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LB', 'Lebanon', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LC', 'St. Lucia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LI', 'Liechtenstein', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LK', 'Sri Lanka', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LR', 'Liberia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LS', 'Lesotho', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LT', 'Lithuania', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LU', 'Luxembourg', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LV', 'Latvia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('LY', 'Libyan Arab Jamahiriya', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MA', 'Morocco', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MC', 'Monaco', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MD', 'Moldova', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ME', 'Montenegro', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MG', 'Madagascar', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MH', 'Marshall Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MK', 'Macedonia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ML', 'Mali', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MM', 'Myanmar', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MN', 'Mongolia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MO', 'Macao', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MP', 'Northern Mariana Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MQ', 'Martinique', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MR', 'Mauritania', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MS', 'Montserrat', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MT', 'Malta', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MU', 'Mauritius', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MV', 'Maldives', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MW', 'Malawi', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MX', 'Mexico', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MY', 'Malaysia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('MZ', 'Mozambique', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NA', 'Namibia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NC', 'New Caledonia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NE', 'Niger', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NF', 'Norfolk Island', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NG', 'Nigeria', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NI', 'Nicaragua', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NL', 'Netherlands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NO', 'Norway', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NP', 'Nepal', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NR', 'Nauru', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NU', 'Niue', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('NZ', 'New Zealand', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('OM', 'Oman', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PA', 'Panama', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PE', 'Peru', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PF', 'French Polynesia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PG', 'Papua New Guinea', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PH', 'Philippines', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PK', 'Pakistan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PL', 'Poland', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PM', 'St. Pierre and Miquelon', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PN', 'Pitcairn Island', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PR', 'Puerto Rico', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PS', 'Palestinian Territory', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PT', 'Portugal', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PW', 'Palau', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('PY', 'Paraguay', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('QA', 'Qatar', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('RE', 'Reunion', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('RO', 'Romania', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('RS', 'Serbia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('RU', 'Russia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('RW', 'Rwanda', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SA', 'Saudi Arabia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SB', 'Solomon Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SC', 'Seychelles', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SD', 'Sudan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SE', 'Sweden', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SG', 'Singapore', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SH', 'St. Helena', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SI', 'Slovenia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SJ', 'Svalbard & Jan Mayen Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SK', 'Slovakia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SL', 'Sierra Leone', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SM', 'San Marino', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SN', 'Senegal', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SO', 'Somalia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SR', 'Suriname', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SS', 'South Sudan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ST', 'Sao Tome and Principe', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SU', 'Soviet Union', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SV', 'El Salvador', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SY', 'Syrian Arab Republic', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('SZ', 'Swaziland', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TC', 'Turks and Caicos Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TD', 'Chad', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TF', 'French Southern Territories', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TG', 'Togo', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TH', 'Thailand', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TJ', 'Tajikistan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TK', 'Tokelau', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TL', 'Timor-Leste', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TM', 'Turkmenistan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TN', 'Tunisia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TO', 'Tonga', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TP', 'East Timor', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TR', 'Turkey', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TT', 'Trinidad and Tobago', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TV', 'Tuvalu', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TW', 'Taiwan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('TZ', 'Tanzania', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('UA', 'Ukraine', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('UG', 'Uganda', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('UM', 'United States Minor Outlying Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('US', 'United States of America', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('UY', 'Uruguay', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('UZ', 'Uzbekistan', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('VA', 'Holy See', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('VC', 'St. Vincent and the Grenadines', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('VE', 'Venezuela', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('VG', 'British Virgin Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('VI', 'US Virgin Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('VN', 'Vietnam', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('VU', 'Vanuatu', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('WF', 'Wallis and Futuna Islands', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('WS', 'Samoa', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('XC', 'Czechoslovakia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('XG', 'East Germany', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('XI', 'Northern Ireland', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('XK', 'Kosovo', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('YE', 'Yemen', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('YT', 'Mayotte', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('YU', 'Yugoslavia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ZA', 'South Africa', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ZM', 'Zambia', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ZR', 'Zaire', '$formattedDate');
            INSERT INTO country (iso_3166_1, english_name, created_at) VALUES ('ZW', 'Zimbabwe', '$formattedDate');
            SQL,
        );
    }
}
