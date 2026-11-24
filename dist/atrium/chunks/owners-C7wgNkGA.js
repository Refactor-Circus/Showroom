// src/components/owners/owners.ts
function e(e) {
	return {
		any_parent: (e?.parent_types ?? null) === null,
		parent_types: [...e?.parent_types ?? []],
		can_be_root: e?.can_be_root ?? !0,
		owns_products: e?.owns_products ?? !0
	};
}
function t(e, t) {
	return {
		parent_types: e.any_parent ? null : t.filter((t) => e.parent_types.includes(t)),
		can_be_root: e.can_be_root,
		owns_products: e.owns_products
	};
}
var n = (e) => encodeURIComponent(e);
function r(e) {
	return `/showroom/owners/${n(e)}`;
}
function i(e) {
	return `/showroom/owners?search=${n(e)}`;
}
function a(e) {
	return `/showroom/owner-types/${n(e)}`;
}
export { t as a, e as i, i as n, a as r, r as t };
