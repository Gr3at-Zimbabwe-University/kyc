<?php

namespace Tests\Feature;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class LegacyAuthenticationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        Schema::create('tblusers', function (Blueprint $table) {
            $table->string('userid', 10)->primary();
            $table->string('fullnames')->nullable();
            $table->string('usergroup')->nullable();
            $table->string('userpassword', 50)->nullable();
        });
        Schema::create('tblaudittrail', function (Blueprint $table) {
            $table->increments('id');
            $table->string('module', 20);
            $table->string('platform', 100);
            $table->string('username', 255);
            $table->string('usertype', 20);
            $table->string('trandescription', 500);
            $table->timestampTz('trandate')->useCurrent();
        });
        Schema::create('tblsession', function (Blueprint $table) {
            $table->increments('sessionid');
            $table->string('modules');
            $table->boolean('status');
        });
    }

    public function test_legacy_staff_password_authenticates_without_changing_the_database_schema(): void
    {
        DB::table('tblusers')->insert([
            'userid' => 'staff01',
            'fullnames' => 'KYC Staff',
            'usergroup' => 'STAFF',
            'userpassword' => 'old-password',
        ]);

        $this->post(route('login.store'), [
            'userid' => 'staff01',
            'password' => 'old-password',
        ])->assertRedirect(route('dashboard'));

        $this->assertDatabaseHas('tblusers', [
            'userid' => 'staff01',
            'userpassword' => 'old-password',
        ]);
        $this->assertDatabaseHas('tblaudittrail', ['username' => 'staff01']);
        $this->assertAuthenticatedLegacyUser('staff01');
    }

    public function test_invalid_legacy_password_is_rejected(): void
    {
        DB::table('tblusers')->insert([
            'userid' => 'staff01',
            'fullnames' => 'KYC Staff',
            'userpassword' => 'old-password',
        ]);

        $this->post(route('login.store'), [
            'userid' => 'staff01',
            'password' => 'incorrect-password',
        ])->assertSessionHasErrors('userid');
        $this->assertDatabaseMissing('tblaudittrail', ['username' => 'staff01']);
    }

    public function test_dashboard_requires_a_legacy_user_session(): void
    {
        $this->get(route('dashboard'))->assertRedirect(route('login'));
    }

    public function test_login_screen_renders(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertSee('Sign in to KYC');
    }

    public function test_authenticated_dashboard_renders(): void
    {
        $this->withSession(['kyc_user' => ['userid' => 'staff01']])
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Student KYC');
    }

    public function test_student_lookup_rejects_invalid_registration_numbers(): void
    {
        $this->withSession(['kyc_user' => ['userid' => 'staff01']])
            ->postJson(route('students.check'), ['key1' => "' OR 1=1 --"])
            ->assertUnprocessable()
            ->assertJsonValidationErrors('key1');
    }

    public function test_student_lookup_returns_the_legacy_student_details(): void
    {
        $this->createStudentTables();
        DB::table('tblstudent')->insert([
            'regnumber' => 'M123456',
            'nationalid' => 'ID123',
            'surname' => 'Student',
            'forenames' => 'Sample',
            'dob3' => '2000-01-02',
            'permanenthomeadd1' => '1 Main Street',
            'permanenthomeadd2' => 'City',
            'permanenthomeadd3' => null,
            'mobileno' => '123456789',
            'email' => 'student@example.test',
        ]);
        DB::table('tblsession')->insert([
            'sessionid' => 2026,
            'modules' => 'REGISTER',
            'status' => true,
        ]);
        DB::table('tblstudentregistration')->insert([
            'regnumber' => 'M123456',
            'sessionid' => 2026,
        ]);
        DB::table('tblcbz_student_ids')->insert([
            'regnumber' => 'M123456',
            'citzn_val' => true,
        ]);

        $this->withSession(['kyc_user' => ['userid' => 'staff01']])
            ->postJson(route('students.check'), ['key1' => 'M123456'])
            ->assertOk()
            ->assertJsonPath('key1', 'SUCCESS')
            ->assertJsonPath('rnum', 'M123456')
            ->assertJsonPath('address', '1 Main Street City');
    }

    public function test_document_capture_screen_renders_for_a_registration_number(): void
    {
        $this->withSession(['kyc_user' => ['userid' => 'staff01']])
            ->post(route('capture.show', ['category' => 'applicationform']), [
                'txtRegNumber' => 'M123456',
            ])
            ->assertOk()
            ->assertSee('Capture Application Form')
            ->assertSee('M123456');
    }

    public function test_capture_options_offer_pending_legacy_documents(): void
    {
        $this->createStudentTables();
        DB::table('tblcbz_student_ids')->insert([
            'regnumber' => 'M123456',
            'kyc_nat_id' => null,
            'kyc_nat_id_reason' => null,
            'kyc_app_form' => null,
            'kyc_app_form_reason' => null,
        ]);

        $this->withSession(['kyc_user' => ['userid' => 'staff01']])
            ->post(route('capture.options'), ['txtRegNumber2' => 'M123456'])
            ->assertOk()
            ->assertSee('National ID')
            ->assertSee('Application Form');
    }

    public function test_biometric_verification_preserves_registration_and_id_collection_checks(): void
    {
        $this->createStudentTables();
        DB::table('tblstudent')->insert([
            'regnumber' => 'M123456',
            'surname' => 'Student',
            'forenames' => 'Sample',
        ]);
        DB::table('tblsession')->insert([
            'sessionid' => 2026,
            'modules' => 'REGISTER',
            'status' => true,
        ]);
        DB::table('tblstudentregistration')->insert([
            'regnumber' => 'M123456',
            'sessionid' => 2026,
            'programmecode' => 'PRG01',
        ]);
        DB::table('tblstudentid')->insert([
            'regnumber' => 'M123456',
            'programmecode' => 'PRG01',
        ]);

        $this->withSession(['kyc_user' => ['userid' => 'staff01']])
            ->postJson(route('students.biometric-check'), ['key1' => 'M123456'])
            ->assertOk()
            ->assertJsonPath('key1', 'SUCCESS')
            ->assertJsonPath('fnames', 'Sample');
    }

    public function test_biometric_photo_is_saved_to_private_storage(): void
    {
        Storage::fake('kyc');
        $image = file_get_contents(public_path('image/logo.png'));

        $this->withSession(['kyc_user' => ['userid' => 'staff01']])
            ->post(route('biometric.store'), [
                'txtRegNumber' => 'M123456',
                'image' => 'data:image/png;base64,'.base64_encode($image),
            ])
            ->assertOk()
            ->assertSee('Biometric photo saved successfully');

        Storage::disk('kyc')->assertExists('biometric/'.hash('sha256', 'M123456').'.png');
    }

    public function test_document_capture_saves_a_private_copy_and_updates_kyc_status(): void
    {
        $this->createStudentTables();
        Storage::fake('kyc');
        DB::table('tblcbz_student_ids')->insert([
            'regnumber' => 'M123456',
            'kyc_nat_id' => null,
            'kyc_nat_id_reason' => null,
        ]);
        $image = file_get_contents(public_path('image/bg.jpg'));

        $this->withSession(['kyc_user' => ['userid' => 'staff01']])
            ->post(route('capture.store', ['category' => 'nationalid']), [
                'txtRegNumber' => 'M123456',
                'image' => 'data:image/jpeg;base64,'.base64_encode($image),
            ])
            ->assertOk()
            ->assertSee('National ID captured successfully.');

        Storage::disk('kyc')->assertExists('nationalid/'.hash('sha256', 'M123456').'.jpg');
        $this->assertDatabaseHas('tblcbz_student_ids', [
            'regnumber' => 'M123456',
            'kyc_nat_id' => false,
            'kyc_nat_id_reason' => 'PENDING',
        ]);
    }

    private function createStudentTables(): void
    {
        Schema::create('tblstudent', function (Blueprint $table) {
            $table->string('regnumber')->primary();
            $table->string('nationalid')->nullable();
            $table->string('surname')->nullable();
            $table->string('forenames')->nullable();
            $table->string('dob3')->nullable();
            $table->string('permanenthomeadd1')->nullable();
            $table->string('permanenthomeadd2')->nullable();
            $table->string('permanenthomeadd3')->nullable();
            $table->string('mobileno')->nullable();
            $table->string('email')->nullable();
        });
        Schema::create('tblstudentregistration', function (Blueprint $table) {
            $table->string('regnumber');
            $table->integer('sessionid');
            $table->string('programmecode')->nullable();
        });
        Schema::create('tblcbz_student_ids', function (Blueprint $table) {
            $table->string('regnumber')->primary();
            $table->boolean('citzn_val')->nullable();
            $table->string('citzn_val_reason')->nullable();
            $table->boolean('kyc_nat_id')->nullable();
            $table->string('kyc_nat_id_reason')->nullable();
            $table->boolean('kyc_app_form')->nullable();
            $table->string('kyc_app_form_reason')->nullable();
            $table->boolean('kyc_por')->nullable();
            $table->string('kyc_por_reason')->nullable();
        });
        Schema::create('tblstudentid', function (Blueprint $table) {
            $table->string('regnumber');
            $table->string('programmecode');
        });
    }

    private function assertAuthenticatedLegacyUser(string $userid): void
    {
        $this->assertSame($userid, session('kyc_user.userid'));
    }
}
