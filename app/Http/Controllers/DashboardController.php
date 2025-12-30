<?php

namespace FluentShipment\App\Http\Controllers;

use FluentShipment\App\Models\User;
use FluentShipment\Framework\Http\Request\Request;

class DashboardController extends Controller
{
	public function index()
	{
        $stats = [
            [
                'id'    => 1,
                'label' => 'Total Shipments',
                'value' => (int)1254,
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#1FC16B"><path d="M18.0049 6.99979H21.0049C21.5572 6.99979 22.0049 7.4475 22.0049 7.99979V19.9998C22.0049 20.5521 21.5572 20.9998 21.0049 20.9998H3.00488C2.4526 20.9998 2.00488 20.5521 2.00488 19.9998V3.99979C2.00488 3.4475 2.4526 2.99979 3.00488 2.99979H18.0049V6.99979ZM4.00488 8.99979V18.9998H20.0049V8.99979H4.00488ZM4.00488 4.99979V6.99979H16.0049V4.99979H4.00488ZM15.0049 12.9998H18.0049V14.9998H15.0049V12.9998Z"></path></svg>',
                'color' => '#E0FAEC',
            ],
            [
                'id'    => 2,
                'label' => 'Total Customer',
                'value' => 234,
                'type'  => 'debits',
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FB4BA3"><path d="M16.0037 9.41421L7.39712 18.0208L5.98291 16.6066L14.5895 8H7.00373V6H18.0037V17H16.0037V9.41421Z"></path></svg>',
                'color' => '#FFEBF4',
            ],
            [
                'id'    => 3,
                'label' => 'Delivered',
                'value' => 2314,
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#335CFF"><path d="M2 20H22V22H2V20ZM4 12H6V19H4V12ZM9 12H11V19H9V12ZM13 12H15V19H13V12ZM18 12H20V19H18V12ZM2 7L12 2L22 7V11H2V7ZM4 8.23607V9H20V8.23607L12 4.23607L4 8.23607ZM12 8C11.4477 8 11 7.55228 11 7C11 6.44772 11.4477 6 12 6C12.5523 6 13 6.44772 13 7C13 7.55228 12.5523 8 12 8Z"></path></svg>',
                'color' => '#EBF1FF',
            ],
            [
                'id'    => 4,
                'label' => 'In Processing',
                'value' => 125,
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#FA7319"><path d="M12.0004 16C14.2095 16 16.0004 14.2091 16.0004 12 16.0004 9.79086 14.2095 8 12.0004 8 9.79123 8 8.00037 9.79086 8.00037 12 8.00037 14.2091 9.79123 16 12.0004 16ZM21.0049 4.00293H3.00488C2.4526 4.00293 2.00488 4.45064 2.00488 5.00293V19.0029C2.00488 19.5552 2.4526 20.0029 3.00488 20.0029H21.0049C21.5572 20.0029 22.0049 19.5552 22.0049 19.0029V5.00293C22.0049 4.45064 21.5572 4.00293 21.0049 4.00293ZM4.00488 15.6463V8.35371C5.13065 8.017 6.01836 7.12892 6.35455 6.00293H17.6462C17.9833 7.13193 18.8748 8.02175 20.0049 8.3564V15.6436C18.8729 15.9788 17.9802 16.8711 17.6444 18.0029H6.3563C6.02144 16.8742 5.13261 15.9836 4.00488 15.6463Z"></path></svg>',
                'color' => '#FFF3EB',
            ],
            [
                'id'    => 5,
                'label' => 'Failed',
                'value' => (int)23,
                'type'  => 'transactions',
                'icon'  => '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="#7D52F4"><path d="M16 16V12L21 17L16 22V18H4V16H16ZM8 2V5.999L20 6V8H8V12L3 7L8 2Z"></path></svg>',
                'color' => '#EFEBFF',
            ],
        ];

        return [
            'stats' => $stats,
        ];

	}
}
