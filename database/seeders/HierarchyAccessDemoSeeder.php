<?php

namespace Database\Seeders;

use App\Models\DemoReport;
use App\Models\DepartmentMembership;
use App\Models\MembershipDecision;
use App\Models\Organization;
use App\Models\OrganizationalUnit;
use App\Models\Person;
use App\Models\SchoolMembership;
use App\Models\ScopedAppointment;
use App\Models\User;
use App\Support\SystemRole;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HierarchyAccessDemoSeeder extends Seeder
{
    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('HierarchyAccessDemoSeeder may only run in local development.');
        }

        $this->call(RolePermissionSeeder::class);
        $this->call(SettingsSeeder::class);

        $credentialsJsonPath = storage_path('app/private/demo-credentials.json');
        $credentialsMdPath = storage_path('app/private/demo-credentials.md');

        File::ensureDirectoryExists(dirname($credentialsJsonPath));

        $forceResetPasswords = filter_var(env('SSU_RESET_DEMO_PASSWORDS', false), FILTER_VALIDATE_BOOL);
        $existingPasswords = [];

        if (! $forceResetPasswords && File::exists($credentialsJsonPath)) {
            $decoded = json_decode((string) File::get($credentialsJsonPath), true);
            if (is_array($decoded)) {
                $existingPasswords = $decoded;
            }
        }

        // 1. Organizations (Union -> 2 Dioceses -> 2 Districts -> 3 Sunday Schools)
        $union = Organization::query()->firstOrCreate(
            ['code' => 'gssu-union'],
            [
                'parent_id' => null,
                'type' => Organization::TYPE_UNION,
                'name_am' => 'የሰንበት ት/ቤቶች አንድነት ማዕከል (ናሙና)',
                'name_en' => 'Sunday Schools Unity Union (Demo)',
                'city_am' => 'አዲስ አበባ',
                'city_en' => 'Addis Ababa',
                'status' => 'active',
            ]
        );

        $diocese1 = Organization::query()->firstOrCreate(
            ['code' => 'diocese-addis-ababa'],
            [
                'parent_id' => $union->id,
                'type' => Organization::TYPE_DIOCESE,
                'name_am' => 'የአዲስ አበባ ሀገረ ስብከት (ናሙና 1)',
                'name_en' => 'Addis Ababa Diocese (Demo 1)',
                'city_am' => 'አዲስ አበባ',
                'city_en' => 'Addis Ababa',
                'status' => 'active',
            ]
        );

        $diocese2 = Organization::query()->firstOrCreate(
            ['code' => 'diocese-bahir-dar'],
            [
                'parent_id' => $union->id,
                'type' => Organization::TYPE_DIOCESE,
                'name_am' => 'የባሕር ዳር ሀገረ ስብከት (ናሙና 2)',
                'name_en' => 'Bahir Dar Diocese (Demo 2)',
                'city_am' => 'ባሕር ዳር',
                'city_en' => 'Bahir Dar',
                'status' => 'active',
            ]
        );

        $districtA1 = Organization::query()->firstOrCreate(
            ['code' => 'district-aa-north'],
            [
                'parent_id' => $diocese1->id,
                'type' => Organization::TYPE_DISTRICT,
                'name_am' => 'የሰሜን አዲስ አበባ ወረዳ ማስተባበሪያ',
                'name_en' => 'North Addis Ababa District',
                'city_am' => 'አዲስ አበባ',
                'city_en' => 'Addis Ababa',
                'status' => 'active',
            ]
        );

        $districtB1 = Organization::query()->firstOrCreate(
            ['code' => 'district-bd-central'],
            [
                'parent_id' => $diocese2->id,
                'type' => Organization::TYPE_DISTRICT,
                'name_am' => 'የባሕር ዳር ማዕከላዊ ወረዳ ማስተባበሪያ',
                'name_en' => 'Bahir Dar Central District',
                'city_am' => 'ባሕር ዳር',
                'city_en' => 'Bahir Dar',
                'status' => 'active',
            ]
        );

        $schoolA = Organization::query()->firstOrCreate(
            ['code' => 'school-a-anketse-birhan'],
            [
                'parent_id' => $districtA1->id,
                'type' => Organization::TYPE_SUNDAY_SCHOOL,
                'name_am' => 'አንቀጸ ብርሃን ሰንበት ት/ቤት (ት/ቤት A)',
                'name_en' => 'Anketse Birhan Sunday School (School A)',
                'city_am' => 'አዲስ አበባ',
                'city_en' => 'Addis Ababa',
                'parish_name_am' => 'ደብረ ብርሃን ቅዱስ ሥላሴ ቤተ ክርስቲያን (ናሙና)',
                'parish_name_en' => 'Debre Birhan Holy Trinity Parish (Demo)',
                'status' => 'active',
            ]
        );

        $schoolB = Organization::query()->firstOrCreate(
            ['code' => 'school-b-finote-selam'],
            [
                'parent_id' => $districtA1->id,
                'type' => Organization::TYPE_SUNDAY_SCHOOL,
                'name_am' => 'ፍኖተ ሰላም ሰንበት ት/ቤት (ት/ቤት B)',
                'name_en' => 'Finote Selam Sunday School (School B)',
                'city_am' => 'አዲስ አበባ',
                'city_en' => 'Addis Ababa',
                'parish_name_am' => 'ደብረ ምሕረት ቅዱስ ሚካኤል ቤተ ክርስቲያን (ናሙና)',
                'parish_name_en' => 'Debre Mihret St. Michael Parish (Demo)',
                'status' => 'active',
            ]
        );

        $schoolC = Organization::query()->firstOrCreate(
            ['code' => 'school-c-felege-hiwot'],
            [
                'parent_id' => $districtB1->id,
                'type' => Organization::TYPE_SUNDAY_SCHOOL,
                'name_am' => 'ፈለገ ሕይወት ሰንበት ት/ቤት (ት/ቤት C)',
                'name_en' => 'Felege Hiwot Sunday School (School C)',
                'city_am' => 'ባሕር ዳር',
                'city_en' => 'Bahir Dar',
                'parish_name_am' => 'ደብረ ገነት ቅዱስ ጊዮርጊስ ቤተ ክርስቲያን (ናሙና)',
                'parish_name_en' => 'Debre Genet St. George Parish (Demo)',
                'status' => 'active',
            ]
        );

        // 2. Internal Units (Union Board, Union Division, and Local School Departments)
        $unionBoard = OrganizationalUnit::query()->firstOrCreate(
            ['organization_id' => $union->id, 'code' => 'union-board'],
            [
                'unit_kind' => OrganizationalUnit::KIND_BOARD,
                'category_code' => 'governance',
                'name_am' => 'የአንድነት ሥራ አመራር ቦርድ',
                'name_en' => 'Union Governing Board',
                'status' => 'active',
            ]
        );

        $unionReportingDiv = OrganizationalUnit::query()->firstOrCreate(
            ['organization_id' => $union->id, 'code' => 'union-reporting-div'],
            [
                'unit_kind' => OrganizationalUnit::KIND_DIVISION,
                'category_code' => 'reporting_coordination',
                'name_am' => 'የዕቅድና ሪፖርት ማስተባበሪያ ዘርፍ',
                'name_en' => 'Planning & Reporting Division',
                'status' => 'active',
            ]
        );

        $deptAHymns = OrganizationalUnit::query()->firstOrCreate(
            ['organization_id' => $schoolA->id, 'code' => 'dept-a-hymns'],
            [
                'unit_kind' => OrganizationalUnit::KIND_DEPARTMENT,
                'category_code' => 'hymns',
                'name_am' => 'የመዝሙር ክፍል',
                'name_en' => 'Hymn Preparation Department',
                'status' => 'active',
            ]
        );

        $deptAMembership = OrganizationalUnit::query()->firstOrCreate(
            ['organization_id' => $schoolA->id, 'code' => 'dept-a-membership'],
            [
                'unit_kind' => OrganizationalUnit::KIND_DEPARTMENT,
                'category_code' => 'membership',
                'name_am' => 'የአባላት ጉዳይ ክፍል',
                'name_en' => 'Membership Affairs Department',
                'status' => 'active',
            ]
        );

        $deptAAudit = OrganizationalUnit::query()->firstOrCreate(
            ['organization_id' => $schoolA->id, 'code' => 'dept-a-audit'],
            [
                'unit_kind' => OrganizationalUnit::KIND_DEPARTMENT,
                'category_code' => 'audit',
                'name_am' => 'የኦዲትና ኢንስፔክሽን ክፍል',
                'name_en' => 'Audit & Inspection Department',
                'status' => 'active',
            ]
        );

        $deptBHymns = OrganizationalUnit::query()->firstOrCreate(
            ['organization_id' => $schoolB->id, 'code' => 'dept-b-hymns'],
            [
                'unit_kind' => OrganizationalUnit::KIND_DEPARTMENT,
                'category_code' => 'hymns',
                'name_am' => 'የመዝሙርና ኪነ ጥበብ ክፍል',
                'name_en' => 'Hymns & Arts Department',
                'status' => 'active',
            ]
        );

        $deptBDev = OrganizationalUnit::query()->firstOrCreate(
            ['organization_id' => $schoolB->id, 'code' => 'dept-b-development'],
            [
                'unit_kind' => OrganizationalUnit::KIND_DEPARTMENT,
                'category_code' => 'development',
                'name_am' => 'የልማት ክፍል',
                'name_en' => 'Development Department',
                'status' => 'active',
            ]
        );

        $deptCMembership = OrganizationalUnit::query()->firstOrCreate(
            ['organization_id' => $schoolC->id, 'code' => 'dept-c-membership'],
            [
                'unit_kind' => OrganizationalUnit::KIND_DEPARTMENT,
                'category_code' => 'membership',
                'name_am' => 'የአባላት ጉዳይ ክፍል',
                'name_en' => 'Membership Affairs Department',
                'status' => 'active',
            ]
        );

        // 3. Helper to provision fictional demo User accounts while preserving existing passwords, names, and locales on rerun
        $credentialRows = [];
        $provisionUser = function (string $email, string $name, string $locale, ?string $spatieRole, string $roleLabel, string $scopeDescription) use (&$existingPasswords, &$credentialRows, $forceResetPasswords): User {
            $plainPassword = $existingPasswords[$email] ?? ('SsuDemo!'.Str::random(10));
            $existingPasswords[$email] = $plainPassword;

            $user = User::query()->where('email', $email)->first();

            if ($user === null) {
                $user = User::query()->create([
                    'name' => $name,
                    'email' => $email,
                    'locale' => $locale,
                    'password' => Hash::make($plainPassword),
                    'email_verified_at' => now(),
                ]);
            } else {
                $updates = [
                    'email_verified_at' => $user->email_verified_at ?? now(),
                ];
                if ($forceResetPasswords) {
                    $updates['password'] = Hash::make($plainPassword);
                }
                $user->update($updates);
            }

            if ($spatieRole !== null) {
                $user->syncRoles([$spatieRole]);
            } else {
                $user->syncRoles([]);
            }

            $credentialRows[] = [
                'email' => $email,
                'password' => $plainPassword,
                'name' => $name,
                'role' => $roleLabel,
                'scope' => $scopeDescription,
            ];

            return $user;
        };

        $unionSuperAdminUser = $provisionUser(
            'union-admin@example.test',
            'መልአከ ብርሃናት ዮሐንስ (Union Super Admin Demo)',
            'am',
            null,
            'Union Super Administrator (Nationwide Governance)',
            'Union Nationwide (Hierarchy, Org Create/Update, Local-Lead Appointments, Released Reports & National Summary — No Private Member Directory or Unreleased Drafts)'
        );

        $leadAUser = $provisionUser(
            'lead-a@example.test',
            'አቤል ተስፋዬ (Abel Tesfaye)',
            'am',
            null,
            'Local Lead A (+ 2nd Appointment: District A1 Officer)',
            'School A (አንቀጸ ብርሃን ሰ/ት/ቤት) [Switchable to District A1]'
        );

        $leadBUser = $provisionUser(
            'lead-b@example.test',
            'ብሩክ ዓለሙ (Biruk Alemu)',
            'am',
            null,
            'Local Lead B',
            'School B (ፍኖተ ሰላም ሰ/ት/ቤት)'
        );

        $coordHymnsAUser = $provisionUser(
            'coord-hymns-a@example.test',
            'ዳዊት ከበደ (Dawit Kebede)',
            'am',
            null,
            'Department Coordinator A (Hymns)',
            'School A — የመዝሙር ክፍል (Hymn Dept)'
        );

        $memberAUser = $provisionUser(
            'member-a@example.test',
            'ሐና Hanna ደሞ',
            'am',
            null,
            'Approved Member A',
            'School A — Own Profile & Assigned Departments'
        );

        $pendingAUser = $provisionUser(
            'pending-a@example.test',
            'ዮናስ በቀለ (Yonas Bekele)',
            'am',
            null,
            'Pending / Unlinked Account',
            'School A — Pending Membership Application Only'
        );

        $districtOfficerUser = $provisionUser(
            'district-aa-north@example.test',
            'ሰለሞን ግርማ (Solomon Girma)',
            'am',
            null,
            'District Officer (North Addis Ababa)',
            'District A1 (Schools A & B Released Reports Only)'
        );

        $dioceseOfficerUser = $provisionUser(
            'diocese-aa@example.test',
            'መምህር ኃይሌ ታደሰ (Memhir Haile Tadesse)',
            'am',
            null,
            'Diocese Officer (Addis Ababa Diocese 1)',
            'Diocese 1 (District A1 & Schools A/B Released Reports)'
        );

        $unionDivisionUser = $provisionUser(
            'union-reporting@example.test',
            'ራሔል አሰፋ (Rahel Assefa)',
            'am',
            null,
            'Union Division Coordinator (Planning & Reporting)',
            'Union — Planning & Reporting Functional Scope'
        );

        $unionBoardUser = $provisionUser(
            'union-board@example.test',
            'ዶ/ር ኤልያስ ወንድሙ (Dr. Elias Wondimu)',
            'en',
            null,
            'Union Board Member',
            'Union Governing Board Summary Scope (Explicit English Locale)'
        );

        $techOperatorUser = $provisionUser(
            'tech-admin@example.test',
            'ቴክኒካል ኦፕሬተር (Technical Operator)',
            'am',
            SystemRole::ADMIN,
            'Technical Operator',
            'Platform Technical Administration (No Super Admin Domain Bypass)'
        );

        // 4. Scoped Appointments (firstOrCreate so owner-modified appointment dates/active flags are preserved on rerun)
        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $unionSuperAdminUser->id,
                'organization_id' => $union->id,
                'role_key' => ScopedAppointment::ROLE_UNION_SUPER_ADMIN,
            ],
            [
                'organizational_unit_id' => null,
                'title_am' => 'የሰንበት ት/ቤቶች አንድነት የበላይ አስተዳዳሪ (Union Super Administrator)',
                'title_en' => 'Union Super Administrator',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $leadAUser->id,
                'organization_id' => $schoolA->id,
                'role_key' => ScopedAppointment::ROLE_SCHOOL_LEAD,
            ],
            [
                'organizational_unit_id' => null,
                'title_am' => 'የሰንበት ት/ቤት ዋና አስተባባሪ (ት/ቤት A)',
                'title_en' => 'Sunday School Lead (School A)',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        // Second appointment for leadAUser to demonstrate safe scope switching without combining privileges
        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $leadAUser->id,
                'organization_id' => $districtA1->id,
                'role_key' => ScopedAppointment::ROLE_DISTRICT_OFFICER,
            ],
            [
                'organizational_unit_id' => null,
                'title_am' => 'የወረዳ ሪፖርት ተወካይ (2ኛ ምደባ — ወረዳ A1)',
                'title_en' => 'District Committee Representative (2nd Scope — District A1)',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $leadBUser->id,
                'organization_id' => $schoolB->id,
                'role_key' => ScopedAppointment::ROLE_SCHOOL_LEAD,
            ],
            [
                'organizational_unit_id' => null,
                'title_am' => 'የሰንበት ት/ቤት ዋና አስተባባሪ (ት/ቤት B)',
                'title_en' => 'Sunday School Lead (School B)',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $coordHymnsAUser->id,
                'organization_id' => $schoolA->id,
                'organizational_unit_id' => $deptAHymns->id,
                'role_key' => ScopedAppointment::ROLE_DEPARTMENT_COORDINATOR,
            ],
            [
                'title_am' => 'የመዝሙር ክፍል አስተባባሪ (ት/ቤት A)',
                'title_en' => 'Hymn Department Coordinator (School A)',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $districtOfficerUser->id,
                'organization_id' => $districtA1->id,
                'role_key' => ScopedAppointment::ROLE_DISTRICT_OFFICER,
            ],
            [
                'organizational_unit_id' => null,
                'title_am' => 'የሰሜን አዲስ አበባ ወረዳ አስተባባሪ',
                'title_en' => 'North Addis Ababa District Officer',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $dioceseOfficerUser->id,
                'organization_id' => $diocese1->id,
                'role_key' => ScopedAppointment::ROLE_DIOCESE_OFFICER,
            ],
            [
                'organizational_unit_id' => null,
                'title_am' => 'የአዲስ አበባ ሀገረ ስብከት ሰ/ት/ቤቶች ማስተባበሪያ ኃላፊ',
                'title_en' => 'Addis Ababa Diocese Officer',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $unionDivisionUser->id,
                'organization_id' => $union->id,
                'organizational_unit_id' => $unionReportingDiv->id,
                'role_key' => ScopedAppointment::ROLE_UNION_DIVISION_COORDINATOR,
            ],
            [
                'title_am' => 'የአንድነት የዕቅድና ሪፖርት ዘርፍ አስተባባሪ',
                'title_en' => 'Union Planning & Reporting Division Coordinator',
                'functional_category' => 'reporting_coordination',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $unionBoardUser->id,
                'organization_id' => $union->id,
                'organizational_unit_id' => $unionBoard->id,
                'role_key' => ScopedAppointment::ROLE_UNION_BOARD_MEMBER,
            ],
            [
                'title_am' => 'የአንድነት ሥራ አመራር ቦርድ አባል',
                'title_en' => 'Union Governing Board Member',
                'functional_category' => 'governance',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        ScopedAppointment::query()->firstOrCreate(
            [
                'user_id' => $techOperatorUser->id,
                'organization_id' => null,
                'role_key' => ScopedAppointment::ROLE_TECHNICAL_OPERATOR,
            ],
            [
                'organizational_unit_id' => null,
                'title_am' => 'የመድረክ ቴክኒካል ኦፕሬተር',
                'title_en' => 'Platform Technical Operator',
                'starts_on' => '2026-01-01',
                'is_active' => true,
            ]
        );

        // 5. People, School Memberships & Department Memberships (firstOrCreate to preserve owner approvals & edits)
        // Person 1: Approved Member A (Mixed Amharic/English name, linked to member-a@example.test, serves in 2 departments)
        $personMemberA = Person::query()->firstOrCreate(
            ['phone' => '+251911000101'],
            [
                'full_name' => 'ሐና Hanna ደሞ',
                'name_en' => 'Hanna Demo',
                'service_notes' => 'በመዝሙር እና በአባላት ጉዳይ ክፍል የምታገለግል ንቁ አባል (Mixed UTF-8 sample)።',
            ]
        );

        $membershipA = SchoolMembership::query()->firstOrCreate(
            ['person_id' => $personMemberA->id, 'organization_id' => $schoolA->id],
            [
                'user_id' => $memberAUser->id,
                'membership_code' => 'SSU-A-001',
                'status' => SchoolMembership::STATUS_APPROVED,
                'approved_by_user_id' => $leadAUser->id,
                'approved_at' => now()->subDays(14),
                'joined_on' => '2025-09-11',
            ]
        );

        DepartmentMembership::query()->firstOrCreate(
            ['school_membership_id' => $membershipA->id, 'organizational_unit_id' => $deptAHymns->id, 'is_active' => true],
            ['role_label_am' => 'መዘምር (Choir Member)', 'starts_on' => '2025-09-15']
        );

        DepartmentMembership::query()->firstOrCreate(
            ['school_membership_id' => $membershipA->id, 'organizational_unit_id' => $deptAMembership->id, 'is_active' => true],
            ['role_label_am' => 'የአባላት ምዝገባ ረዳት', 'starts_on' => '2026-01-10']
        );

        // Person 2: Pending applicant in School A (linked to pending-a@example.test)
        $personPendingA = Person::query()->firstOrCreate(
            ['phone' => '+251911000102'],
            [
                'full_name' => 'ዮናስ በቀለ (Yonas Bekele)',
                'name_en' => 'Yonas Bekele',
                'service_notes' => null,
            ]
        );

        SchoolMembership::query()->firstOrCreate(
            ['person_id' => $personPendingA->id, 'organization_id' => $schoolA->id],
            [
                'user_id' => $pendingAUser->id,
                'membership_code' => 'SSU-A-PEND-002',
                'status' => SchoolMembership::STATUS_PENDING,
                'application_note' => 'አዲስ የአባልነት እና የመለያ ትስስር ጥያቄ — የሰንበት ት/ቤቱ ዋና አስተባባሪ ማጽደቂያ የሚጠብቅ።',
            ]
        );

        // Person 3: Registered person in School A WITHOUT a login account (CORE-004)
        $personNoAccountA = Person::query()->firstOrCreate(
            ['phone' => '+251911000103'],
            [
                'full_name' => 'ማርታ ገብሩ (ያለ መለያ የተመዘገበች አባል)',
                'name_en' => 'Marta Gebru',
                'service_notes' => 'የመግቢያ መለያ (User login) ሳይኖራት በሰንበት ት/ቤቱ መዝገብ የተመዘገበች አባል።',
            ]
        );

        $membershipNoAccountA = SchoolMembership::query()->firstOrCreate(
            ['person_id' => $personNoAccountA->id, 'organization_id' => $schoolA->id],
            [
                'user_id' => null,
                'membership_code' => 'SSU-A-003',
                'status' => SchoolMembership::STATUS_APPROVED,
                'approved_by_user_id' => $leadAUser->id,
                'approved_at' => now()->subDays(30),
                'joined_on' => '2025-10-01',
            ]
        );

        DepartmentMembership::query()->firstOrCreate(
            ['school_membership_id' => $membershipNoAccountA->id, 'organizational_unit_id' => $deptAHymns->id, 'is_active' => true],
            ['role_label_am' => 'መዘምር', 'starts_on' => '2025-10-05']
        );

        // Person 4: Approved member in School B (to test cross-school member isolation!)
        $personMemberB = Person::query()->firstOrCreate(
            ['phone' => '+251911000201'],
            [
                'full_name' => 'ሳሙኤል ታደሰ (ት/ቤት B አባል)',
                'name_en' => 'Samuel Tadesse',
                'service_notes' => 'የፍኖተ ሰላም ሰንበት ት/ቤት (ት/ቤት B) የግል አባል መዝገብ።',
            ]
        );

        $membershipB = SchoolMembership::query()->firstOrCreate(
            ['person_id' => $personMemberB->id, 'organization_id' => $schoolB->id],
            [
                'user_id' => null,
                'membership_code' => 'SSU-B-001',
                'status' => SchoolMembership::STATUS_APPROVED,
                'approved_by_user_id' => $leadBUser->id,
                'approved_at' => now()->subDays(20),
                'joined_on' => '2025-11-01',
            ]
        );

        DepartmentMembership::query()->firstOrCreate(
            ['school_membership_id' => $membershipB->id, 'organizational_unit_id' => $deptBHymns->id, 'is_active' => true],
            ['role_label_am' => 'መዘምር', 'starts_on' => '2025-11-01']
        );

        // Person 5: Suspended member in School A with historical department participation & decision history (TASK-028 sample)
        $personSuspendedA = Person::query()->firstOrCreate(
            ['phone' => '+251911000104'],
            [
                'full_name' => 'ተስፋዬ መኮንን (ለጊዜው የታገደ የአባልነት ናሙና)',
                'name_en' => 'Tesfaye Mekonnen',
                'service_notes' => 'የአባልነት የሕይወት ዑደት (Suspended / Ended History) ማሳያ ናሙና መዝገብ።',
            ]
        );

        $membershipSuspendedA = SchoolMembership::query()->firstOrCreate(
            ['person_id' => $personSuspendedA->id, 'organization_id' => $schoolA->id],
            [
                'user_id' => null,
                'membership_code' => 'SSU-A-SUSP-004',
                'status' => SchoolMembership::STATUS_SUSPENDED,
                'account_link_status' => SchoolMembership::LINK_NONE,
                'status_reason' => 'ለ3 ወራት የትምህርት ፈቃድ በመውሰዱ የአገልግሎት ተሳትፎው ለጊዜው ታግዷል።',
                'created_by_user_id' => $leadAUser->id,
                'approved_by_user_id' => $leadAUser->id,
                'approved_at' => now()->subDays(60),
                'suspended_by_user_id' => $leadAUser->id,
                'suspended_at' => now()->subDays(10),
                'joined_on' => '2025-09-01',
            ]
        );

        DepartmentMembership::query()->firstOrCreate(
            [
                'school_membership_id' => $membershipSuspendedA->id,
                'organizational_unit_id' => $deptAHymns->id,
                'starts_on' => '2025-09-01',
            ],
            [
                'role_label_am' => 'መዘምር (የቀድሞ ተሳትፎ)',
                'ends_on' => '2026-01-15',
                'is_active' => false,
                'assigned_by_user_id' => $leadAUser->id,
                'ended_by_user_id' => $leadAUser->id,
            ]
        );

        // Seed initial decision history records idempotently for demo memberships
        MembershipDecision::query()->firstOrCreate(
            [
                'school_membership_id' => $membershipA->id,
                'action' => MembershipDecision::ACTION_APPROVED,
            ],
            [
                'organization_id' => $schoolA->id,
                'actor_user_id' => $leadAUser->id,
                'target_user_id' => $memberAUser->id,
                'from_status' => SchoolMembership::STATUS_PENDING,
                'to_status' => SchoolMembership::STATUS_APPROVED,
                'reason' => 'የአባልነት እና የመለያ ትስስር በሰንበት ት/ቤቱ ዋና አስተባባሪ ጸድቋል።',
            ]
        );

        MembershipDecision::query()->firstOrCreate(
            [
                'school_membership_id' => $membershipNoAccountA->id,
                'action' => MembershipDecision::ACTION_APPROVED,
            ],
            [
                'organization_id' => $schoolA->id,
                'actor_user_id' => $leadAUser->id,
                'target_user_id' => null,
                'from_status' => null,
                'to_status' => SchoolMembership::STATUS_APPROVED,
                'reason' => 'ያለ መግቢያ መለያ በሰንበት ት/ቤቱ መዝገብ ተመዝግቦ የጸደቀ አባልነት።',
            ]
        );

        MembershipDecision::query()->firstOrCreate(
            [
                'school_membership_id' => $membershipSuspendedA->id,
                'action' => MembershipDecision::ACTION_APPROVED,
            ],
            [
                'organization_id' => $schoolA->id,
                'actor_user_id' => $leadAUser->id,
                'target_user_id' => null,
                'from_status' => null,
                'to_status' => SchoolMembership::STATUS_APPROVED,
                'reason' => 'የመጀመሪያ የአባልነት ምዝገባ እና ማጽደቂያ።',
            ]
        );

        MembershipDecision::query()->firstOrCreate(
            [
                'school_membership_id' => $membershipSuspendedA->id,
                'action' => MembershipDecision::ACTION_SUSPENDED,
            ],
            [
                'organization_id' => $schoolA->id,
                'actor_user_id' => $leadAUser->id,
                'target_user_id' => null,
                'from_status' => SchoolMembership::STATUS_APPROVED,
                'to_status' => SchoolMembership::STATUS_SUSPENDED,
                'reason' => 'ለ3 ወራት የትምህርት ፈቃድ በመውሰዱ የአገልግሎት ተሳትፎው ለጊዜው ታግዷል።',
            ]
        );

        // 6. Demo Reports proving the local-draft vs. lead-released upward visibility boundary (firstOrCreate to preserve owner status transitions)
        DemoReport::query()->firstOrCreate(
            [
                'organization_id' => $schoolA->id,
                'organizational_unit_id' => $deptAHymns->id,
                'period_label' => '2019 ዓ.ም. 1ኛ ሩብ ዓመት (Q1)',
            ],
            [
                'category_code' => 'hymns',
                'title_am' => 'የአንቀጸ ብርሃን ሰ/ት/ቤት የመዝሙር ክፍል 1ኛ ሩብ ዓመት ሪፖርት (የተለቀቀ)',
                'summary_am' => 'በሩብ ዓመቱ 12 የበዓላት መዝሙራት ጥናት ተካሂዷል። በሰንበት ት/ቤቱ ዋና አስተባባሪ ጸድቆ ለወረዳ፣ ለሀገረ ስብከት እና ለአንድነት የተለቀቀ ሪፖርት።',
                'activity_count' => 12,
                'status' => DemoReport::STATUS_RELEASED,
                'submitted_by_user_id' => $coordHymnsAUser->id,
                'approved_by_user_id' => $leadAUser->id,
                'released_at' => now()->subDays(5),
            ]
        );

        DemoReport::query()->firstOrCreate(
            [
                'organization_id' => $schoolA->id,
                'organizational_unit_id' => $deptAHymns->id,
                'period_label' => '2019 ዓ.ም. 2ኛ ሩብ ዓመት (Q2 Draft)',
            ],
            [
                'category_code' => 'hymns',
                'title_am' => 'የመዝሙር ክፍል 2ኛ ሩብ ዓመት የውስጥ ረቂቅ ሪፖርት (ያልጸደቀ Draft)',
                'summary_am' => 'ይህ ሪፖርት በክፍሉ አስተባባሪ በረቂቅ ደረጃ የተያዘ ሲሆን በሰንበት ት/ቤቱ ዋና አስተባባሪ ስላልጸደቀ ለወረዳ፣ ለሀገረ ስብከትም ሆነ ለአንድነት አይታይም (REP-003 / REP-010)።',
                'activity_count' => 4,
                'status' => DemoReport::STATUS_DRAFT,
                'submitted_by_user_id' => $coordHymnsAUser->id,
                'approved_by_user_id' => null,
                'released_at' => null,
            ]
        );

        DemoReport::query()->firstOrCreate(
            [
                'organization_id' => $schoolA->id,
                'organizational_unit_id' => $deptAMembership->id,
                'period_label' => '2019 ዓ.ም. 1ኛ ሩብ ዓመት (Q1 Local Review)',
            ],
            [
                'category_code' => 'membership',
                'title_am' => 'የአባላት ጉዳይ ክፍል ሪፖርት — ለዋና አስተባባሪ የቀረበ (Submitted Locally)',
                'summary_am' => 'ለሰንበት ት/ቤቱ ዋና አስተባባሪ ግምገማ የቀረበ፤ ነገር ግን ወደ በላይ አካል ገና ያልተለቀቀ። ለመዝሙር ክፍል አስተባባሪም ሆነ ለበላይ አካላት አይታይም።',
                'activity_count' => 6,
                'status' => DemoReport::STATUS_SUBMITTED_LOCAL,
                'submitted_by_user_id' => $leadAUser->id,
                'approved_by_user_id' => null,
                'released_at' => null,
            ]
        );

        DemoReport::query()->firstOrCreate(
            [
                'organization_id' => $schoolA->id,
                'organizational_unit_id' => $deptAAudit->id,
                'period_label' => '2019 ዓ.ም. 1ኛ ሩብ ዓመት (Q1 Audit)',
            ],
            [
                'category_code' => 'audit',
                'title_am' => 'የኦዲትና ኢንስፔክሽን ክፍል የተለቀቀ ሪፖርት (Functional Scope Test)',
                'summary_am' => 'ይህ የኦዲት ሪፖርት የተለቀቀ ቢሆንም፣ የአንድነት የዕቅድና ሪፖርት ዘርፍ አስተባባሪ (union-reporting@example.test) ከሥራ ዘርፉ ውጭ ስለሆነ ማየት አይችልም (REP-011)።',
                'activity_count' => 2,
                'status' => DemoReport::STATUS_RELEASED,
                'submitted_by_user_id' => $leadAUser->id,
                'approved_by_user_id' => $leadAUser->id,
                'released_at' => now()->subDays(3),
            ]
        );

        DemoReport::query()->firstOrCreate(
            [
                'organization_id' => $schoolB->id,
                'organizational_unit_id' => $deptBDev->id,
                'period_label' => '2019 ዓ.ም. 1ኛ ሩብ ዓመት (Q1 Draft)',
            ],
            [
                'category_code' => 'development',
                'title_am' => 'የፍኖተ ሰላም ሰ/ት/ቤት (ት/ቤት B) የልማት ክፍል የውስጥ ረቂቅ',
                'summary_am' => 'ት/ቤት B ገና የጸደቀ ሪፖርት ስላልለቀቀ በወረዳና በሀገረ ስብከት የሪፖርት ምሉዕነት ሰንጠረዥ ላይ «ያልተለቀቀ / Missing» ተብሎ ይታያል፤ ይህ ረቂቅ ግን አይገለጽም።',
                'activity_count' => 5,
                'status' => DemoReport::STATUS_DRAFT,
                'submitted_by_user_id' => $leadBUser->id,
                'approved_by_user_id' => null,
                'released_at' => null,
            ]
        );

        DemoReport::query()->firstOrCreate(
            [
                'organization_id' => $schoolC->id,
                'organizational_unit_id' => $deptCMembership->id,
                'period_label' => '2019 ዓ.ም. 1ኛ ሩብ ዓመት (Q1)',
            ],
            [
                'category_code' => 'membership',
                'title_am' => 'የፈለገ ሕይወት ሰ/ት/ቤት (ት/ቤት C — ባሕር ዳር ሀገረ ስብከት) የተለቀቀ ሪፖርት',
                'summary_am' => 'በባሕር ዳር ሀገረ ስብከት ሥር የሚገኝ የተለቀቀ ሪፖርት። ለአዲስ አበባ ሀገረ ስብከት እና ለሰሜን አዲስ አበባ ወረዳ ኃላፊዎች አይታይም (403)።',
                'activity_count' => 9,
                'status' => DemoReport::STATUS_RELEASED,
                'submitted_by_user_id' => null,
                'approved_by_user_id' => null,
                'released_at' => now()->subDays(2),
            ]
        );

        // Save Git-ignored local credentials files
        File::put($credentialsJsonPath, json_encode($existingPasswords, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        $mdLines = [
            '# Sunday School Unity — Local Hierarchy/Access Demo Credentials (TASK-024)',
            '',
            '> **Git-ignored local file** (`storage/app/private/demo-credentials.md`). Fictional personas only; never commit or use in production.',
            '',
            '## Three Main Accounts for Manual Comparison',
            '',
            '1. **Union Super Administrator:** `union-admin@example.test` (Password: `'.$existingPasswords['union-admin@example.test'].'`)',
            '2. **Sunday School Lead (School A):** `lead-a@example.test` (Password: `'.$existingPasswords['lead-a@example.test'].'`)',
            '3. **Technical Operator:** `tech-admin@example.test` (Password: `'.$existingPasswords['tech-admin@example.test'].'`)',
            '',
            '## All Seeded Demo Personas (11 Accounts)',
            '',
            '| Persona / Role | Email (`@example.test`) | Password | Active Scope / Notes |',
            '|---|---|---|---|',
        ];

        foreach ($credentialRows as $row) {
            $mdLines[] = sprintf(
                '| %s | `%s` | `%s` | %s |',
                $row['role'],
                $row['email'],
                $row['password'],
                $row['scope'],
            );
        }

        File::put($credentialsMdPath, implode(PHP_EOL, $mdLines).PHP_EOL);
    }
}
