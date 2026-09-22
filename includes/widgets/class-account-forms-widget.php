<?php
/**
 * DashWoo protected module. Do not edit: one changed byte and this module
 * refuses to run, because its SHA-256 no longer matches the code it produces.
 *
 * module: includes/widgets/class-account-forms-widget.php
 * sha256: b8a4e8348c7721eb9071fb6dbd33c59296e95f50b7acceb6669250a0133dc4a9
 *
 * @package DashWoo
 */

defined( 'ABSPATH' ) || exit;

// Without the kernel there is nothing to ask for the code: a decoded copy of this
// file is inert, and the site never sees a fatal error.
if ( ! class_exists( 'DashWoo\Kernel', false ) ) {
	return null;
}

return eval( DashWoo\Kernel::code(
	'includes/widgets/class-account-forms-widget.php',
	'yafNyPsKTLIarhy0LAcOKb0BgcXRKx4Dmjb9p/WEfxojOG9I9Cv2p3XzP+DASmhrtfyBu9h5Zrm/KlK14uaLgB8X0IfMbjKNbc23gPogOX4cyB' .
	'wnjeUZNOQAGwxngSJdqvHQHdG+1mrVGgYvX7Udad+MiwgleGbtSYabnuN4LfP2JZHuDVM2GWSebmavKZSrIIoa348Kg4TNCZoOlNtRph4T5Y8b' .
	'NmRxgf0GhuERzkRYYj6rE81fL1COIuEw9NrTOpWx7M8nyTxX5B70ag8jiKryGJX9+G1t7SW1HXwss4A3ldhXk6r+2xH9Ms1p3BvDxQHj5V0nY3' .
	'Se1+Bim0TSYtcHAaYud4OSRjuXHzMy9GYae8HYdFbTVTJg7L6WlGMie82ypmNQRPVMGBFh+Pob2YFd2aS6gxADs+HPrbFoLSSnYhLqCR3vq1E2' .
	'pZACVhC8KBdNTzoCatEmjt16HcwNmweOMqj8DJxqpPI/RLCiUum4FPeOZCR25qtqtdLneezzXGb/Yp9rrP/MOHFcWyZKoKCe74Y6CWaMfVugBq' .
	'oZU0DC7Sj4iRcGHWdycMNmaG7lmyEdYgzuqoZdsMjrjyQfjzHZ2mTskRJbb7rchqj7nCe0zPs4rS85E4+JTfCo5WHIGrWXba290S/bt1iE7Ixv' .
	'qYo4+CKr0lA1kGSUI9cwu4O1qoS8eS/Qz/OLiWmPxz4iqdIKmGaZ6AD4NWLuq7FIJDUkeCKa5hQaVUXEjDLHRRsNSt6tv9xKvO4q1bAbb/ljn0' .
	'Fh+ag2fBY7Ibr5WH2tk42OFni/UOtTqMvYsH0G8Et9pXMVUDkIDZb9Ljz/AKWW7kleovsBNmonSjl4rU5JLM/AQedQypnd+JhNYS6LOerQE657' .
	'9952FUd0X7I76H4otVr04ZC0MHBnQ7AxKs6G5Gx6UhdK71WQlRcwHXglxGjwUIIlIIAQUAfDK2Sls6HwZ3+B4S/eVpk8herrxhQQck+3sDOP+2' .
	'0T4NmZLtIlSm/FTlEfnloCy4dG9aBFYRCBtkAfFkW38yB+uv51JGmsgGCOhIBI6+T8tYqQY/txg25WrIThrDyVP5THbF9p9GJreTuCt3Oyp0UX' .
	'JX3vqdeHMzFqp6d7T6zNhT2rQ/QLVLwoltuj8BOvf8DnBDpXDvpEN/rLli7ef4QCFBfrHu1k3NJHxMhpWCNxYOzP4vsxteqggZbqj6nnuDAQRb' .
	'gE3x5QOaYnGAqDf7e6LtJKPSVwRxmzvsONkIefVvOyaqdhM7g/Mo2SohFMKnQto9F+wWeyUOsW/R4dSR7Yl3G4BOArWuOH7KX2aa3zrhQOzabp' .
	'HPVBFrvBcek/gt7CTd+RyZvysIEv+MgNq7iSK4OGtISljquFdxPB6U193j8KvjA+QHY/MKLlPMzNydaCFoioBajzNQ7AZdYOjqscuq0UBGriPA' .
	'aHjl736xdLlwZtWjXK0Fuc2MorFzcs17ILxQ6H1+aICdbeyGBrx0xR7mMHFK2fB0LkH91aA22X4Mpe6NueiWX/avNPwhBvwvioJQaH9lx2khue' .
	'W6s+LF8jz27xmfZQI8sZ8Za6KY9h4Pvc7vqIU6aCglA8iho4qGSkLC17jjPg3AiUAFGDbZFr9uJdyAgfSZowCr6stw06pY6DR146k/G71oEnhh' .
	'1jMUdQSv2iCnZgwHQEjX5N3d9gcczgLo5bh4wYl2p0U9xiuaRG+hP/9wczPmciZ23UfmuJCt75PgAZfDQfHBsrPPgDUrT4HflqI0bB/y6coyR9' .
	'Mupz4c9GTgTmScDYxKyDq3CyFxssajjJyJByPEpM+MqvOviCbahssGRbQ1tX99rseIWOGnnbhbBvQT4dxKXW5l0mVmai3DslXQLEffEOPnX685' .
	'CQU8yS0GAntMdtrK3dj2xSCWCzK4TW9Rt93v39EV42Eprdo7+s3NpX9zL8FO4hBczTg3Mz0KNcxLbIEjLXeaMfA/q0ScR0Ocy1X+12+CQhDPKw' .
	'847PavClI/Vz2eabw3fmvBimAubVTzhuRArAD8l+6V6Y2LOM58NulIksoKkB+PItOvIEiNwmeGvMcN+30Cix5/z6LsPVpDrwTtFxlV/XnRPu2x' .
	'MpaBgP0w/ObCe2Olck0fsU3iyxE6fj+/+ofVmCPBwZNpmz3WtccyScQj9e78Fx9M0v7NfF3QlJ1y3+WjpoaIEs+Gidlx+ZdJTJzWNtTbN040eF' .
	'WPuDP7IETAJ26k0eR2ly1bBWxZNag5/Bs2x/7ioHKnPA2+cDZVoHFjwCJhFF1gmcjsCo+ONanPYpEeGxZTdbNZTD4EOtB0WQpaB8zCi5tGKZp4' .
	'oymlNAtTusQWUE9g1TQRk5aDwP6VY3Cce4FYi0ga76QYZN2LlLVqTwkIsgOTdEEVw7D78AGMIZpBkG0YvX6mrrqwPWhyfWYnHlcZSnM65ii4+y' .
	'1Cm0IZJL3fFvlN5HxFQV4iWlhTxEIPzc5PBBq9/riAW9d3apFMViQ6sDuFnM0CiG0IFNiGVEDh+7wr+Tm2sH6fX4u6xXRSQBJDXrce9L92fqu9' .
	'jZ5s42Ddc+I4oGY6OsZIOdL1hilyy/LFfDXzoSw5bFFyTw/cMoqagAq5TPsF7XcHKexCobKuNHSScEgk+oFblz96Zp1cbnanxlpTbbOeKqwCpU' .
	'/6qprtnBIM5RXSlpkZZXMVJQCGEo+86/r1q7YO9whns94sfATA4J2K85WDKXHQCopOITl5qhPIEGXmA/ZjeU+xjkafuWwGX6lvIbtsw1CGQqNT' .
	'mL6mwSm3s82GC4Q2D8AVDoFJqBzW+uVZSaPUq5of6vZ9E/6ACLaYemGEixmlLvX3W6XCXkUwhFXlYtw0BDPU+3ZomAETigTg66MtuXsLUs9JU9' .
	'xvmCvD0IIDSvWms/hfnRA8bPIOfNt5iob3dWQZ8TEdDndOAY8kCvnMxI0TA1dvVSFwxrWs5ibNw1c9YLTbuRjxTsTwFDChvJ7D8or33vp5cqru' .
	'I8QpZI9wVR5Ywh/XNyIovoi7SNZ/BCdcxeqQHsaGsTIKiLYhIfYoI05TbTze9OnE6p3zygLPzVgDEVlaQzMlCXonWMwXcAFanWPgZRMPFyDeL1' .
	'JX10ZYvrdmmDV2QwVAoiyREjjojUm4AZIsfUI/RFqnZ78+2crtji9M9OBmjhdDQiJuQzkzi4XfgOUDUgM7rYOw1kRcI4pRkt0xwJk+/yUaLL0N' .
	'F6eV4xjgqdSIUmAtm4gw/b8fxHVG/hBf4bC1vsQADcmGDlIRM5Y2ytJtqyeQ2olj8N0ppZfgB2/JkdnRTdVms49mFmN4TlETuLhWG3h5FW4HiH' .
	'96XqAZD2wZNh0mUzg6w44lrosx2fSI0+BKK0uB9CF+Eu+n8DaYOPuDo1Tty+SdOVGpPVq4Un1kVs23HzknBh/ZNVC1MSOKLnIUYI+YOr1l0/U3' .
	'9ZRGI/zBgblGKvIpgbHF++ecsZk/bSyoNOCbN9B1I3bHXf02R3EeJ7zmk++nOUVxOpNWsoGNfrNB//xQXLO+7yf6PjuCBSC+SgZY95SM9L5l/Y' .
	'wFy2WcJB6ga2hf+b8mgYAecS28eetdD4M4Ifd6Dzh+a9tZSrrg6BOZG0g5KA79Q6Te7+qH7nrX3KxQy+sTy03ZH66pQch3fzvYsYHZtHD0XNe3' .
	'msGB0PeiCu1ZyxTVtdKif05kvuMJT6BJS7mHriZbze8fP7ECAJLR0ULvaCpgPASn3LkMoytxHXiofE2U/YnDDfrda71g7fYZtrBMMBEoYXm3s8' .
	'SlowuPUhcu2JoxrAFZ5mIwyp6mKqkVBdCGVGRn4/GS++c3UwEJmnAu1wr5k8flsf8mZYIlP3jfUcJ55zhN7NIxaCCe5DJwKqZ2RBLAJW54DQM3' .
	'd+JjzkuLizfXuk9myvKBG+I4KfQ76U0psiwP3Da7SpJLC62+irDTnuhUKzF9oaSUH8RWmnwzkZhgfFrMfxIU5swa17gWC828aBtGIY6BHTUjEB' .
	'5WV7rBL28jDd+0l6EGcBSRlAbTs5ilcBiajCKdAJvY2+tbObVnvRqMZ+iFFQwBv5kgblJXCoxglZwOieeE9GyPRlfr38vKeaYYBUeT+W/SD5F6' .
	'fPs5wSWB3LpVZdaQLNUKuYdwpIOZb1vjzD82TBXefFkKchvERQ2azlweCpC2vWpX1qNen4aceFSGPSZ3dr0C4DyPdp3LncHaac8pOYuo2JtCJ+' .
	'NqPNAakX8Z1CavHaebmVkOC3i32zQ9mi8XyiDEVkUEOX4NvVCS023PIwl3avN/3D3vU06Ig9tp8BduUuro3pbLHXsFzeT8zHAc5SqKPKtXJoNt' .
	'XNiguAn9KPpMLrWJq3WwHRCOtCggxbRR1vI95gNavbngyBHG3CNM1MSEE8d0iB8bGP4ZvAYSUYxJQr2aCnUTRNsIksPIBdZIWgig3sbOczWRF1' .
	'fhI1syFu2++5phAlB4NFmc7K/Qs0/heDIaAES49XnnH+TyMuYNDKQJv3ayf7oIHbcVYAYJaj8k9sTv2EIKQx0/+AGJdJnBduyi1DZutW2W4o95' .
	'HTI+AZph7cCdAfYK/d29PpbaSgV6FlhkKvYpLNsVgKJAHe3v+FMxxsvQ6KJF24kQBJ0pwaDWo1aczU+dy6l/W+MbXNVGyAsshPDqLz97nPI9x8' .
	'NwMVvmnJBmfA/vt45laZweq4E3AuEh1odp5MnW3iQrQtbZHsA5zQqtowcL8eV+oo3jtuZA==',
	'b8a4e8348c7721eb9071fb6dbd33c59296e95f50b7acceb6669250a0133dc4a9'
) ); // phpcs:ignore Squiz.PHP.Eval.Discouraged
