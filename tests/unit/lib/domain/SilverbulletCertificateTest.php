<?php
require_once(__DIR__ . '../../../../../config/config.php');
require_once(__DIR__ . '../../../../../core/EntityWithDBProperties.php');
require_once(__DIR__ . '../../../../../core/DBConnection.php');
use lib\domain\SilverbulletCertificate;
use lib\domain\SilverbulletUser;

class SilverBulletCertificateTest extends PHPUnit_Framework_TestCase {
    
    /**
     * 
     * @var SilverbulletCertificate
     */
    private $newCertificate;
    
    
    /**
     * 
     * @var SilverbulletCertificate
     */
    private $faultyCertificate;
    
    private $profile;
    
    /**
     * 
     * @var int|string
     */
    private $profileId;
    
    
    /**
     * 
     * @var SilverbulletUser
     */
    private $newUser = null;
    
    /**
     *
     * @var SilverbulletUser
     */
    
    private $faultyUser = null;
    
    protected function setUp(){
        $this->profile = new MockProfileSilverbullet(DBConnection::handle('INST'));
        $this->profileId = $this->profile->identifier;
        
        $this->newUser = new SilverbulletUser($this->profileId, 'testusername');
        $this->newUser->save();
        
        $this->faultyUser = new SilverbulletUser($this->profileId, 'faultytestusername');
        
        $this->newCertificate = new SilverbulletCertificate($this->newUser);
        $this->faultyCertificate = new SilverbulletCertificate($this->faultyUser);
    }
    
    public function testNewCertificateSuccess() {
        $this->newCertificate->save();
        $this->assertNotEmpty($this->newCertificate->getIdentifier());
        
        $existingCertificate = SilverbulletCertificate::prepare($this->newCertificate->getIdentifier());
        $existingCertificate->load();
        $this->assertNotEmpty($existingCertificate->getIdentifier());
        
        $oneTimeToken = $existingCertificate->getOneTimeToken();
        $this->assertNotEmpty($oneTimeToken);
        
        $expiry = $existingCertificate->get(SilverbulletCertificate::EXPIRY);
        $this->assertNotEmpty($expiry);
        
        $list = SilverbulletCertificate::getList($this->newUser);
        $found = false;
        foreach ($list as $certificate) {
            if($certificate->getIdentifier() == $this->newCertificate->getIdentifier()){
                $found = true;
                break;
            }
        }
        $this->assertTrue($found);
        
        $this->newCertificate->setCertificateDetails('0498230984238023', 'testcommonname', "+1 week");
        $this->assertTrue($this->newCertificate->save());
        
        $existingCertificate = SilverbulletCertificate::prepare($this->newCertificate->getIdentifier());
        $existingCertificate->load();
        $this->assertEquals('0498230984238023', $existingCertificate->getSerialNumber());
        $this->assertEquals('testcommonname', $existingCertificate->getCommonName());
        
        $result = $this->newCertificate->delete();
        $this->assertTrue($result);
    }
    
    public function testNewCertificateFailure(){
        $this->faultyCertificate->save();
        $this->assertEmpty($this->faultyCertificate->getIdentifier());
        
        $existingCertificate = SilverbulletCertificate::prepare($this->faultyCertificate->getIdentifier());
        $existingCertificate->load();
        
        $list = SilverbulletCertificate::getList($this->faultyUser);
        $found = false;
        foreach ($list as $certificate) {
            if($certificate->getIdentifier() == $this->faultyCertificate->getIdentifier()){
                $found = true;
                break;
            }
        }
        $this->assertFalse($found);
    }
    
    public function testFaultyCertificateLoadFailure(){
        $this->faultyCertificate->load();
        $this->assertEmpty($this->faultyCertificate->getIdentifier());
    }
    
    protected function tearDown(){
        $this->newUser->delete();
        if(!empty($this->faultyCertificate)){
            $this->faultyCertificate->delete();
        }
        $this->profile->delete();
    }
    
}
