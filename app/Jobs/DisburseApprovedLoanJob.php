<?php

namespace App\Jobs;

use phpseclib3\Net\SFTP;
use App\Models\Loan\Loan;
use App\Events\BankApprovedLoan;
use League\Flysystem\Filesystem;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use League\Flysystem\PhpseclibV3\SftpAdapter;
use League\Flysystem\PhpseclibV3\ConnectionProvider;

class DisburseApprovedLoanJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct()
    {
        //
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    { // Fetch the necessary data from your application
        $headerData = [
            'debitCustomerId' => '445624', // First 6 digits of the debit customer number
            'debitCustomerAccountNumber' => '4456240011',
            'totalDebitAmount' => '0', // Calculate the total debit amount from approved loans
            'currencyCode' => 'KES',
            'effectiveDate' => date('dmY'),
        ];

        // Get only the approved loans
        $approvedLoans = Loan::where('status', 'approved')->get();

        // Calculate the total debit amount
        $totalDebitAmount = $approvedLoans->sum('approved_amount');
        $headerData['totalDebitAmount'] = number_format($totalDebitAmount, 2, '.', '');

        // Generate the header record
        $headerRecord = implode(',', [
            $headerData['debitCustomerId'],
            $headerData['debitCustomerAccountNumber'],
            $headerData['totalDebitAmount'],
            $headerData['currencyCode'],
            $headerData['effectiveDate'],
        ]);

        // Generate the detail records
        $detailRecords = [];
        foreach ($approvedLoans as $loan) {
            $convertedPhoneNumber = $this->convertPhoneNumber($loan->client->mobile);
            $detailRecord = implode(',', [
                number_format($loan->approved_amount, 2, '.', ''),
                'A', // Assuming 'A' for all disbursements (adhoc flag)
                $loan->client->full_name,
                $convertedPhoneNumber, // Phone number
                'MMTS', // Assuming 'MPESA' for all disbursements (payment type)
                '99002', // Clearing code for M-Pesa
                'fanikishamicrofinancebank@gmail.com', // Optional email address
                'Loan Disbursement',
                'Loan Disbursement',
                'Loan Disbursement',
                'Loan Disbursement',
                'Loan Disbursement',
                'Loan Disbursement',
                'Loan Disbursement',
            ]);
            $detailRecords[] = $detailRecord;
        }

        // Join the header and detail records
        $fileContent = implode("\n", array_merge([$headerRecord], $detailRecords));

        // Save the file content or return it
        $fileName = 'S2S4456' . date('ymdi').'1.txt';
        $encryptname='S2S4456' . date('ymdi').'1';
        file_put_contents(storage_path('app/' . $fileName), $fileContent);
        $storagePath = storage_path('app/' . $fileName);
        $workingDir ='/home/fanikishamicrofinancebank.com/fmb/H2H/Working/'. $fileName;
        $scriptPath = "/home/fanikishamicrofinancebank.com/fmb/H2H_Direct_Banking_Bulk_File_Encrypt/EncryptTool.sh";
        //save file generated  to /home/fanikishamicrofinancebank.com/H2H/Working
        file_put_contents($workingDir ,$fileContent);


        $baseDir = '/home/fanikishamicrofinancebank.com/fmb/H2H_Direct_Banking_Bulk_File_Encrypt/';
        $inputFileName = $fileName;
        $deleteFlag = 0; // Set to 0 if you don't want to delete the raw file


        $output = shell_exec("bash $scriptPath $baseDir $inputFileName  $deleteFlag 2>&1");
         // Transfer encrypted file to server
         $this->transferFileToSftp($encryptname);



    }

    private function convertPhoneNumber($phoneNumber)
    {
        // Convert phone number from 07xxxxxxx or 01xxxxxxx to 2547xxxxxxx or 2541xxxxxxx
        if (preg_match('/^0[17]\d{8}$/', $phoneNumber)) {
            return '254' . substr($phoneNumber, 1);
        }
        return $phoneNumber; // return original if it doesn't match the pattern
    }



    public function transferFileToSftp($encryptname) {
        $sftpConfig = [
            'host' => '196.1.132.43',
            'username' => 'fmb',
            'password' => '989Whfmb4bDeAM@10',
            'port' => 2222,
            'root' => '/home/fmb/incoming/', // Adjust as needed
            'timeout' => 30,
            ];

            $encryptedFilename = $encryptname.'_E' . '.txt';
            $encryptedFilePath = '/home/fanikishamicrofinancebank.com/fmb/H2H/Encrypted/encryptedfile/'. $encryptedFilename;
            // Remote file path on the SFTP server
            $remoteFilePath = '/home/fmb/incoming/' . basename($encryptedFilePath);

            // Transfer the encrypted file
            //$this->transferFileToSftp($encryptedFilePath, $remoteFilePath, $sftpConfig);
            $sftp = new SFTP($sftpConfig['host'],$sftpConfig['port']);
            if (!$sftp->login($sftpConfig['username'], $sftpConfig['password'])) {
                echo "Login failed\n";
                exit;
            }


            if ($sftp->put($remoteFilePath, $encryptedFilePath, SFTP::SOURCE_LOCAL_FILE)) {
                echo "File transferred successfully to $remoteFilePath\n";
                exit;
            } else {
                echo "File transfer failed\n";
                exit;
            }

         return "file transfer successful";

        }
}
