<?php

return [
    'default' => [
        'operating_revenue' => [
            'Loan Interest Income' => [
                'description' => 'Income generated from interest on various loan products',
                'multi_currency' => true,
                'base_code' => '101000',
                'accounts' => [
                    'Wezesha Biz Advance Interest' => [
                        'description' => 'Interest income from Wezesha Biz Advance loans',
                        'gl_code' => '101000',
                    ],
                    'Inua Biz Advance Interest' => [
                        'description' => 'Interest income from Inua Biz Advance loans',
                        'gl_code' => '101001',
                    ],
                    'Safari Mafuta Advance Interest' => [
                        'description' => 'Interest income from Safari Mafuta Advance loans',
                        'gl_code' => '101002',
                    ],
                    'Safari Maisha Advance Interest' => [
                        'description' => 'Interest income from Safari Maisha Advance loans',
                        'gl_code' => '101003',
                    ],
                    'Instawage Interest' => [
                        'description' => 'Interest income from Instawage loans',
                        'gl_code' => '101004',
                    ],
                ],
            ],
            'Other Loan Fees & Commissions' => [
                'description' => 'Fees generated from loan administration and extensions',
                'multi_currency' => true,
                'base_code' => '102000',
                'accounts' => [
                    'Wezesha Biz Advance Administration Fees' => [
                        'description' => 'Administration fees from Wezesha Biz Advance',
                        'gl_code' => '102000',
                    ],
                    'Inua Biz Advance Administration Fees' => [
                        'description' => 'Administration fees from Inua Biz Advance',
                        'gl_code' => '102001',
                    ],
                    'Safari Mafuta Advance Administration Fees' => [
                        'description' => 'Administration fees from Safari Mafuta Advance',
                        'gl_code' => '102002',
                    ],
                    'Safari Maisha Advance Administration Fees' => [
                        'description' => 'Administration fees from Safari Maisha Advance',
                        'gl_code' => '102003',
                    ],
                    'Instawage Administration Fees' => [
                        'description' => 'Administration fees from Instawage',
                        'gl_code' => '102004',
                    ],
                    'Wezesha Biz Advance Extension Fees' => [
                        'description' => 'Extension fees from Wezesha Biz Advance',
                        'gl_code' => '102005',
                    ],
                    'Inua Biz Advance Extension Fees' => [
                        'description' => 'Extension fees from Inua Biz Advance',
                        'gl_code' => '102006',
                    ],
                    'Safari Mafuta Advance Extension Fees' => [
                        'description' => 'Extension fees from Safari Mafuta Advance',
                        'gl_code' => '102007',
                    ],
                    'Safari Maisha Advance Extension Fees' => [
                        'description' => 'Extension fees from Safari Maisha Advance',
                        'gl_code' => '102008',
                    ],
                    'Instawage Extension Fees' => [
                        'description' => 'Extension fees from Instawage',
                        'gl_code' => '102009',
                    ],
                    'Reminder Charges [sms]' => [
                        'description' => 'Charges for SMS reminders',
                        'gl_code' => '102010',
                    ],
                    'Late Payment Charges [penalties]' => [
                        'description' => 'Charges for late payments',
                        'gl_code' => '102011',
                    ],
                ],
            ],
            'Other Operating Income' => [
                'description' => 'Income from sources other than loan interest and fees',
                'multi_currency' => true,
                'base_code' => '103000',
                'accounts' => [
                    'Registration Fees' => [
                        'description' => 'Fees charged for registration',
                        'gl_code' => '103000',
                    ],
                    'Bad Debts Recovered' => [
                        'description' => 'Income from recovery of previously written-off bad debts',
                        'gl_code' => '103001',
                    ],
                    'Miscellaneous Income' => [
                        'description' => 'Income from various other sources',
                        'gl_code' => '103002',
                    ],
                    'Investment Income' => [
                        'description' => 'Income from investments',
                        'gl_code' => '103003',
                    ],
                    'Interest on Bank Deposits' => [
                        'description' => 'Interest earned on bank deposits',
                        'gl_code' => '103004',
                    ],
                ],
            ],
            'Commissions & Fees' => [
                'description' => 'Income from commissions and fees',
                'multi_currency' => true,   
                'base_code' => '104000',
                'accounts' => [
                    'Loan Insurance Fee' => [
                        'description' => 'Fees charged for loan insurance',
                        'gl_code' => '104000',
                    ],
                ],
            ],
        ],
        'operating_expense' => [
            'Staff Cost' => [
                'description' => 'Expenses related to employee compensation and benefits',
                'multi_currency' => true,
                'base_code' => '201000',
                'accounts' => [
                    'Salary' => [
                        'description' => 'Regular employee salaries',
                        'gl_code' => '201000',
                    ],
                    'Staff Performance Incentives & Bonuses' => [
                        'description' => 'Performance-based incentives and bonuses',
                        'gl_code' => '201001',
                    ],
                    'Provident fund [Employer]' => [
                        'description' => 'Employer contributions to provident fund',
                        'gl_code' => '201002',
                    ],
                    'Staff Personal Accident Cover' => [
                        'description' => 'Accident insurance for staff',
                        'gl_code' => '201003',
                    ],
                    'Staff Medical Expenses' => [
                        'description' => 'Medical expenses for staff',
                        'gl_code' => '201004',
                    ],
                    'Staff Education' => [
                        'description' => 'Educational expenses for staff',
                        'gl_code' => '201005',
                    ],
                    'Staff Transfer Expenses' => [
                        'description' => 'Expenses related to staff transfers',
                        'gl_code' => '201006',
                    ],
                    'Staff Welfare Expenses' => [
                        'description' => 'Staff welfare and well-being expenses',
                        'gl_code' => '201007',
                    ],
                    'NSSF[Employer]' => [
                        'description' => 'Employer contributions to NSSF',
                        'gl_code' => '201008',
                    ],
                    'Housing Levy [Employer]' => [
                        'description' => 'Employer contributions to housing levy',
                        'gl_code' => '201009',
                    ],
                    'NITA' => [
                        'description' => 'NITA expenses',
                        'gl_code' => '201009',
                    ],
                ],
            ],
            'Governance Expenses' => [
                'description' => 'Expenses related to board management and governance',
                'multi_currency' => true,
                'base_code' => '202000',
                'accounts' => [
                    'Board Allowances' => [
                        'description' => 'Allowances paid to board members',
                        'gl_code' => '202000',
                    ],
                    'Other Board Expenses' => [
                        'description' => 'Other expenses related to the board',
                        'gl_code' => '202001',
                    ],
                ],
            ],
            'Operating Expenses' => [
                'description' => 'Day-to-day expenses required to run the business',
                'multi_currency' => true,
                'base_code' => '203000',
                'accounts' => [
                    'Marketing' => [
                        'description' => 'Marketing and promotional expenses',
                        'gl_code' => '203000',
                    ],
                    'Entertainment & PR' => [
                        'description' => 'Entertainment and public relations expenses',
                        'gl_code' => '203001',
                    ],
                    'Printing & Stationery' => [
                        'description' => 'Printing and stationery expenses',
                        'gl_code' => '203002',
                    ],
                    'Post and telephone' => [
                        'description' => 'Postal and telephone expenses',
                        'gl_code' => '203003',
                    ],
                    'Internet Connectivity' => [
                        'description' => 'Internet service expenses',
                        'gl_code' => '203004',
                    ],
                    'SMS Subscription' => [
                        'description' => 'SMS service subscription expenses',
                        'gl_code' => '203005',
                    ],
                    'Cloud Server Hosting' => [
                        'description' => 'Cloud server hosting expenses',
                        'gl_code' => '203006',
                    ],
                    'ICT Hardware Maintenance Costs' => [
                        'description' => 'Maintenance costs for ICT hardware',
                        'gl_code' => '203007',
                    ],
                    'Water, Fuel and Electricity' => [
                        'description' => 'Utility expenses',
                        'gl_code' => '203008',
                    ],
                    'Rates and Rents' => [
                        'description' => 'Rental and rate expenses',
                        'gl_code' => '203009',
                    ],
                    'Permits and Licences' => [
                        'description' => 'Expenses for permits and licenses',
                        'gl_code' => '203010',
                    ],
                    'General Repairs and Maintenance' => [
                        'description' => 'General repair and maintenance expenses',
                        'gl_code' => '203011',
                    ],
                    'Casual Labour' => [
                        'description' => 'Expenses for casual labor',
                        'gl_code' => '203012',
                    ],
                    'Security' => [
                        'description' => 'Security expenses',
                        'gl_code' => '203013',
                    ],
                    'General Office Expenses' => [
                        'description' => 'General office expenses',
                        'gl_code' => '203014',
                    ],
                    'Management Meeting Expenses' => [
                        'description' => 'Expenses for management meetings',
                        'gl_code' => '203015',
                    ],
                    'Staff Field Travel' => [
                        'description' => 'Travel expenses for staff in the field',
                        'gl_code' => '203016',
                    ],
                    'Staff Tea & Refreshments' => [
                        'description' => 'Expenses for staff refreshments',
                        'gl_code' => '203017',
                    ],
                    'Mileage Claim' => [
                        'description' => 'Mileage claims for staff travel',
                        'gl_code' => '203018',
                    ],
                    'External Transport' => [
                        'description' => 'External transportation expenses',
                        'gl_code' => '203019',
                    ],
                    'General Insurance' => [
                        'description' => 'General insurance expenses',
                        'gl_code' => '203020',
                    ],
                    'Mpesa B2C Charges' => [
                        'description' => 'Charges for Mpesa B2C transactions',
                        'gl_code' => '203021',
                    ],
                    'Mpesa Till Charges' => [
                        'description' => 'Charges for Mpesa Till transactions',
                        'gl_code' => '203022',
                    ],
                    'Banking Charges' => [
                        'description' => 'Bank charges and fees',
                        'gl_code' => '203023',
                    ],
                    'Software Upgrade Expenses' => [
                        'description' => 'Expenses for software upgrades',
                        'gl_code' => '203024',
                    ],
                    'Amortisation-Software' => [
                        'description' => 'Amortization expenses for software',
                        'gl_code' => '203025',
                    ],
                    'Bad Debts Recovery Expenses' => [
                        'description' => 'Expenses related to recovering bad debts',
                        'gl_code' => '203026',
                    ],
                    'Sundry Expenses' => [
                        'description' => 'Miscellaneous expenses',
                        'gl_code' => '203027',
                    ],
                ],
            ],
            'Administrative Expenses' => [
                'description' => 'Expenses related to administrative functions',
                'multi_currency' => true,
                'base_code' => '204000',
                'accounts' => [
                    'Subscriptions' => [
                        'description' => 'Subscription expenses',
                        'gl_code' => '204000',
                    ],
                    'CSR' => [
                        'description' => 'Corporate Social Responsibility expenses',
                        'gl_code' => '204001',
                    ],
                ],
            ],
            'Financial Cost of Lending' => [
                'description' => 'Costs associated with lending activities',
                'multi_currency' => true,
                'base_code' => '205000',
                'accounts' => [
                    'Interest on Working Capital Loans' => [
                        'description' => 'Interest expenses on working capital loans',
                        'gl_code' => '205000',
                    ],
                    'Interest Rebates and Waivers' => [
                        'description' => 'Rebates and waivers on interest',
                        'gl_code' => '205001',
                    ],
                    'Investment Management Costs' => [
                        'description' => 'Costs for managing investments',
                        'gl_code' => '205002',
                    ],
                    'Working Capital Acquisition Fees' => [
                        'description' => 'Fees for acquiring working capital',
                        'gl_code' => '205003',
                    ],
                    'Provision for Bad Debts' => [
                        'description' => 'Provisions made for bad debts',
                        'gl_code' => '205004',
                    ],
                    'Bad Debts Written Off' => [
                        'description' => 'Bad debts written off',
                        'gl_code' => '205005',
                    ],
                ],
            ],
            'Professional Fees and Expenses' => [
                'description' => 'Fees for professional services',
                'multi_currency' => true,
                'base_code' => '206000',
                'accounts' => [
                    'Valuation Fees' => [
                        'description' => 'Fees for valuation services',
                        'gl_code' => '206001',
                    ],
                    'Audit Fees' => [
                        'description' => 'Fees for auditing services',
                        'gl_code' => '206002',
                    ],
                    'Legal Fees' => [
                        'description' => 'Fees for legal services',
                        'gl_code' => '206003',
                    ],
                    'Consultancy' => [
                        'description' => 'Fees for consultancy services',
                        'gl_code' => '206004',
                    ],
                ],
            ],
            'Depreciation Expenses' => [
                'description' => 'Expenses due to depreciation of assets',
                'multi_currency' => false,
                'base_code' => '207000',
                'accounts' => [
                    'Depn. On Computers' => [
                        'description' => 'Depreciation on computer assets',
                        'gl_code' => '207000',
                    ],
                    'Depn. On Furniture & Equipment' => [
                        'description' => 'Depreciation on furniture and equipment',
                        'gl_code' => '207001',
                    ],
                ],
            ],
        ],
        'non_current_asset' => [
            'Fixed Assets' => [
                'description' => 'Long-term tangible assets owned by the company',
                'multi_currency' => true,
                'base_code' => '301000',
                'accounts' => [
                    'Office Furniture & Equipment' => [
                        'description' => 'Furniture and equipment used in the office',
                        'gl_code' => '301000',
                    ],
                    'Computers Hardware' => [
                        'description' => 'Computer hardware assets',
                        'gl_code' => '301001',
                    ],
                    'Other Business machines' => [
                        'description' => 'Other business machines and equipment',
                        'gl_code' => '301002',
                    ],
                ],
            ],
            'Intangible Assets' => [
                'description' => 'Non-physical assets owned by the company',
                'multi_currency' => true,
                'base_code' => '302000',
                'accounts' => [
                    'Computers Software' => [
                        'description' => 'Software assets',
                        'gl_code' => '302000',
                    ],
                ],
            ],
        ],
        'current_asset' => [
            'Current Assets' => [
                'description' => 'Assets expected to be converted to cash within one year',
                'multi_currency' => true,
                'base_code' => '303000',
                'accounts' => [
                    'Wezesha Biz Advance' => [
                        'description' => 'Wezesha Biz Advance assets',
                        'gl_code' => '303000',
                    ],
                    'Inua Biz Advance' => [
                        'description' => 'Inua Biz Advance assets',
                        'gl_code' => '303001',
                    ],
                    'Safari Mafuta Advance' => [
                        'description' => 'Safari Mafuta Advance assets',
                        'gl_code' => '303002',
                    ],
                    'Safari Maisha Advance' => [
                        'description' => 'Safari Maisha Advance assets',
                        'gl_code' => '303003',
                    ],
                    'Instawage' => [
                        'description' => 'Instawage assets',
                        'gl_code' => '303004',
                    ],
                    'Investments' => [
                        'description' => 'Investment assets',
                        'gl_code' => '303005',
                    ],
                    'Wezesha Biz Advance Accrued Interest' => [
                        'description' => 'Accrued interest from Wezesha Biz Advance',
                        'gl_code' => '303006',
                    ],
                    'Inua Biz Advance Accrued interest' => [
                        'description' => 'Accrued interest from Inua Biz Advance',
                        'gl_code' => '303007',
                    ],
                    'Safari Mafuta Advance Accrued Interest' => [
                        'description' => 'Accrued interest from Safari Mafuta Advance',
                        'gl_code' => '303008',
                    ],
                    'Safari Maisha Advance Accrued Interest' => [
                        'description' => 'Accrued interest from Safari Maisha Advance',
                        'gl_code' => '303009',
                    ],
                    'Instawage Accrued Interest' => [
                        'description' => 'Accrued interest from Instawage',
                        'gl_code' => '303010',
                    ],
                    'Staff Medical Fund' => [
                        'description' => 'Staff medical fund assets',
                        'gl_code' => '303011',
                    ],
                    'Petty Cash' => [
                        'description' => 'Petty cash funds',
                        'gl_code' => '303012',
                    ],
                    'Imprest' => [
                        'description' => 'Imprest funds',
                        'gl_code' => '303013',
                    ],
                    'Rent deposit' => [
                        'description' => 'Deposits paid for rent',
                        'gl_code' => '303014',
                    ],
                    'Prepaid Rent' => [
                        'description' => 'Rent paid in advance',
                        'gl_code' => '303015',
                    ],
                    'Utility Deposit' => [
                        'description' => 'Deposits paid for utilities',
                        'gl_code' => '303016',
                    ],
                    'Other Debtors' => [
                        'description' => 'Other amounts owed to the company',
                        'gl_code' => '303017',
                    ],
                    'Tax Recoverable' => [
                        'description' => 'Recoverable tax payments',
                        'gl_code' => '303018',
                    ],
                    'Stationeries Stock' => [
                        'description' => 'Stock of stationery items',
                        'gl_code' => '303019',
                    ],
                    'Marketing Stock' => [
                        'description' => 'Stock of marketing materials',
                        'gl_code' => '303020',
                    ],
                ],
            ],

            'Bank' => [
                'description' => 'Bank accounts and related assets',
                'multi_currency' => true,
                'base_code' => '304000',
                'bank_account_type' => 'depository',
                'accounts' => [
                    'Equity Bank Ltd Garden City [1750186171520]' => [
                        'description' => 'Equity Bank account',
                        'gl_code' => '304000',
                    ],
                    'Mpesa B2C [5506840]' => [
                        'description' => 'Mpesa B2C account',
                        'gl_code' => '304001',
                    ],
                    'Mpesa Merchant [4999702]' => [
                        'description' => 'Mpesa Merchant account',
                        'gl_code' => '304002',
                    ],
                    'Mpesa Settlement Account' => [
                        'description' => 'Mpesa settlement account',
                        'gl_code' => '304003',
                    ],
                    'Bank Settlement Account' => [
                        'description' => 'Bank settlement account',
                        'gl_code' => '304004',
                    ],
                ],
            ],
        ],
        'current_liability' => [
            'Current Liabilities' => [
                'description' => 'Obligations that are due within one year',
                'multi_currency' => true,
                'base_code' => '401000',
                'accounts' => [
                    'Repayments Account' => [
                        'description' => 'Account for loan repayments',
                        'gl_code' => '401000',
                    ],
                    'Accrued Depn. On Furniture & Equipment' => [
                        'description' => 'Accrued depreciation on furniture and equipment',
                        'gl_code' => '401001',
                    ],
                    'Accrued Depn. On MIS & Computers' => [
                        'description' => 'Accrued depreciation on MIS and computers',
                        'gl_code' => '401002',
                    ],
                    'Accrued Amortisation-Software' => [
                        'description' => 'Accrued amortization on software',
                        'gl_code' => '401003',
                    ],
                    'Accrued Expenses' => [
                        'description' => 'Expenses that have been incurred but not yet paid',
                        'gl_code' => '401004',
                    ],
                    'Mpesa Merchant Account 4999702 Suspense' => [
                        'description' => 'Suspense account for Mpesa Merchant',
                        'gl_code' => '401005',
                    ],
                    'Excise Duty' => [
                        'description' => 'Excise duty liabilities',
                        'gl_code' => '401006',
                    ],
                    'CRB Fees' => [
                        'description' => 'Credit Reference Bureau fees',
                        'gl_code' => '401007',
                    ],
                    'Chattels/Affidavit Fees' => [
                        'description' => 'Fees for chattels and affidavits',
                        'gl_code' => '401008',
                    ],
                    'Interest on Working Capital Loans Payable' => [
                        'description' => 'Interest payable on working capital loans',
                        'gl_code' => '401009',
                    ],
                    'Provision for Bad Debts' => [
                        'description' => 'Provisions for bad debts',
                        'gl_code' => '401010',
                    ],
                    'Loan Insurance Premium' => [
                        'description' => 'Insurance premiums for loans',
                        'gl_code' => '401011',
                    ],
                    'Staff Medical Fund' => [
                        'description' => 'Medical fund for staff',
                        'gl_code' => '401012',
                    ],
                    'Creditors Account' => [
                        'description' => 'Account for creditors',
                        'gl_code' => '401013',
                    ],
                    'Tax Liability' => [
                        'description' => 'Tax liabilities',
                        'gl_code' => '401014',
                    ],
                    'Wezesha Biz Advance Accrued Interest' => [
                        'description' => 'Accrued interest on Wezesha Biz Advance',
                        'gl_code' => '401015',
                    ],
                    'Inua Biz Advance Accrued interest' => [
                        'description' => 'Accrued interest on Inua Biz Advance',
                        'gl_code' => '401016',
                    ],
                    'Safari Mafuta Advance Accrued Interest' => [
                        'description' => 'Accrued interest on Safari Mafuta Advance',
                        'gl_code' => '401017',
                    ],
                    'Safari Maisha Advance Accrued Interest' => [
                        'description' => 'Accrued interest on Safari Maisha Advance',
                        'gl_code' => '401018',
                    ],
                    'Instawage Accrued Interest' => [
                        'description' => 'Accrued interest on Instawage',
                        'gl_code' => '401019',
                    ],
                ],
            ],
        ],
        'non_current_liability' => [
            'Long Term loans' => [
                'description' => 'Loans with repayment terms extending beyond one year',
                'multi_currency' => true,
                'base_code' => '402000',
                'accounts' => [
                    'Africa Impact Initiative' => [
                        'description' => 'Loan from Africa Impact Initiative',
                        'gl_code' => '402000',
                    ],
                    'Banzi Ventures W/C Loan' => [
                        'description' => 'Working capital loan from Banzi Ventures',
                        'gl_code' => '402001',
                    ],
                ],
            ],
        ],
        'equity' => [
            'Capital Funds' => [
                'description' => 'Funds contributed by shareholders and retained earnings',
                'multi_currency' => true,
                'base_code' => '501000',
                'accounts' => [
                    'Shareholders Funds' => [
                        'description' => 'Funds contributed by shareholders',
                        'gl_code' => '501000',
                    ],
                    'Retained Earnings' => [
                        'description' => 'Accumulated earnings retained in the business',
                        'gl_code' => '501001',
                    ],
                ],
            ],
        ],
    ],
];