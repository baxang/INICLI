# INICLI

INICLI is a command-line INIpay interface. Speak it like 'ini-klie'.

INIpay is a SDK for payments integration that is provided by Inicis, a Korean online payments provider. However it only supports PHP and Java officially and unlikely to add support for other *minor* languages in the foreseeable future. So I created this handy command-line interface that can be executed from my Ruby on Rails project.

The purpose of INICLI is not only providing language-independent interface that is also easily be embedded in your new and existing applications, but to provide a clean and easy tutorial for developers who are having difficulties understanding and integrating INIpay50 into their projects which I believe are most of them. (Examples coming soon!)

INICLI now supports credit card only.

## Requirements
- xNIX operating system. INICLI is tested on OS X 10.8 and Ubuntu 11.
- PHP > 5.2 with mcrypt, openssl, xml, sockets, mbstring extensions enabled. `inicli.php` checks all the depencencies.
- INIpay50 SDK which can be obtained from your business contact at Inicis.

## Installation

    $ git clone [repo] INICLI
    $ cd INICLI
    $ mv ~/Download/INIpay50 vendor


## Usage

    $ `which php` /path/to/inicli --command=[chkfake|securepay|cancel] --mid=[your mid] --admin=[your admin id] --params='[parameters in JSON format]'
    
To avoid copyright issues INICLI doesn't include INIpay50. You first have to copy INIpay50 under `vendor` directory as it is so you can find INILib.php under `vendor/INIpay50/libs`. Otherwise you need to modify source code to set `INIFactory::$INIPAY_ROOT` on your own.

## TODO
- Support for other payments methods such as bank transfer, mobile.
- Support for INIpayMobile payment.
- Ruby on Rails example code.
- Better cli options such as supporting short code.

## License
You are free to do whatever you want to. Feedback and contributions welcomed.
