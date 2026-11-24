import { t as e } from "./CatalogActionError-BRUgojP9.js";
import { A as t, C as n, D as r, O as i, d as a, f as o, j as s } from "./catalog-DCoO3A8a.js";
import { h as c } from "./structure-DbqcM-iO.js";
import { AtriumErrorCard as ee, AtriumExtension as l, AtriumIntegrationScreen as te, AtriumNoAccess as ne, atriumStatus as u, integrationData as d, useAtriumIntegration as f, useAtriumScreen as re } from "@refactor-circus/atrium";
import { UAlert as ie, UBadge as p, UButton as ae, UCard as oe, UFormField as se, USkeleton as ce } from "@refactor-circus/atrium/ui";
import { Fragment as m, computed as h, createBlock as g, createCommentVNode as _, createElementBlock as v, createElementVNode as y, createTextVNode as b, createVNode as x, defineComponent as S, isRef as le, openBlock as C, reactive as ue, ref as w, renderList as T, shallowRef as E, toDisplayString as D, unref as O, useTemplateRef as de, vModelCheckbox as fe, vModelSelect as k, watch as pe, withCtx as A, withDirectives as j, withModifiers as M } from "vue";
import { useI18n as N } from "vue-i18n";
var P = "showroom:", me = [
	"upsert",
	"create",
	"update"
], he = ["jsonl", "csv"];
function F(e) {
	let t = e?.flow_prefix;
	return typeof t == "string" ? t : P;
}
function ge(e, t) {
	return e.filter((e) => e.slug.startsWith(t)).map((e) => e.slug);
}
function I(e) {
	let t = e.created_at ? new Date(e.created_at).getTime() : NaN;
	return Number.isNaN(t) ? 0 : t;
}
function _e(e, t = 20) {
	return e.flat().sort((e, t) => I(t) - I(e)).slice(0, t);
}
function L(e) {
	let t = e.error?.message;
	return typeof t == "string" && t !== "" ? t : null;
}
function R(e) {
	switch (e) {
		case "completed": return "success";
		case "failed": return "error";
		case "running":
		case "pending":
		case "waiting": return "info";
		case "rolling_back": return "warning";
		default: return "neutral";
	}
}
function ve(e) {
	return {
		format: e.format,
		...e.family ? { family: e.family } : {},
		...e.category ? { category: e.category } : {},
		...e.scope ? { scope: e.scope } : {},
		published: e.published
	};
}
// src/components/transfers/TransferRunsTable.vue?vue&type=script&setup=true&lang.ts
var z = {
	class: "w-full text-left text-sm",
	"data-testid": "showroom-transfers-table"
}, B = { class: "text-muted" }, V = { class: "py-2 pr-3 font-medium" }, H = { class: "py-2 pr-3 font-medium" }, U = { class: "py-2 pr-3 font-medium" }, W = { class: "py-2 pr-3 font-medium" }, G = {
	key: 0,
	class: "py-2"
}, K = { class: "divide-y divide-default" }, q = ["data-testid"], ye = { class: "py-2 pr-3 font-mono text-xs" }, be = { class: "py-2 pr-3" }, J = {
	key: 0,
	class: "mt-1 text-xs text-error"
}, Y = { class: "py-2 pr-3 text-muted" }, X = { class: "py-2 pr-3 text-muted" }, Z = {
	key: 0,
	class: "py-2 text-right"
}, Q = { class: "flex justify-end gap-2" }, xe = /* @__PURE__ */ S({
	__name: "TransferRunsTable",
	props: {
		runs: {},
		rowActions: {}
	},
	setup(e) {
		let { t } = N();
		return (n, r) => {
			let i = p, o = l;
			return C(), v("table", z, [y("thead", B, [y("tr", null, [
				y("th", V, D(O(t)("showroom.transfers.flow")), 1),
				y("th", H, D(O(t)("showroom.transfers.status")), 1),
				y("th", U, D(O(t)("showroom.transfers.started")), 1),
				y("th", W, D(O(t)("showroom.transfers.finished")), 1),
				e.rowActions.length > 0 ? (C(), v("th", G)) : _("", !0)
			])]), y("tbody", K, [(C(!0), v(m, null, T(e.runs, (n) => (C(), v("tr", {
				key: n.id,
				"data-testid": `showroom-transfer-run-${n.id}`
			}, [
				y("td", ye, D(n.flow), 1),
				y("td", be, [x(i, {
					color: O(R)(n.status),
					variant: "subtle"
				}, {
					default: A(() => [b(D(n.status), 1)]),
					_: 2
				}, 1032, ["color"]), O(L)(n) ? (C(), v("p", J, D(O(L)(n)), 1)) : _("", !0)]),
				y("td", Y, D(O(a)(n.started_at ?? n.created_at) ?? O(t)("showroom.catalog.none")), 1),
				y("td", X, D(O(a)(n.finished_at) ?? O(t)("showroom.catalog.none")), 1),
				e.rowActions.length > 0 ? (C(), v("td", Z, [y("div", Q, [(C(!0), v(m, null, T(e.rowActions, (e) => (C(), g(o, {
					key: e.key,
					extension: e,
					record: n.id
				}, null, 8, ["extension", "record"]))), 128))])])) : _("", !0)
			], 8, q))), 128))])]);
		};
	}
}), Se = {
	class: "space-y-4",
	"data-testid": "showroom-transfers"
}, Ce = { class: "text-xl font-semibold" }, we = { class: "space-y-4" }, Te = { class: "grid gap-4 lg:grid-cols-2" }, Ee = { class: "font-semibold" }, De = ["value"], Oe = { class: "font-semibold" }, ke = ["value"], Ae = { value: "" }, je = ["value"], Me = { value: "" }, Ne = ["value"], Pe = { value: "" }, Fe = ["value"], Ie = { class: "flex items-center gap-2 text-sm" }, Le = { class: "font-semibold" }, Re = {
	key: 4,
	class: "text-sm text-muted",
	"data-testid": "showroom-transfers-no-runs"
}, $ = /* @__PURE__ */ S({
	__name: "index",
	setup(a) {
		let { t: l, locale: p } = N(), S = s(), P = f("impex"), I = i(), L = i(), { data: R, error: z } = re("showroom.transfers"), B = h(() => R.value ? F(R.value.data.options) : z.value ? F(null) : null), V = h(() => z.value ? [] : R.value?.data.sections["row.actions"] ?? []), H = E([]), U = w(!1), W = w(null), G = w(!1);
		async function K() {
			let e = P.client, t = B.value;
			if (e && t !== null) try {
				let n = d(await e.GET("/flows")).data, r = await Promise.all(ge(n, t).map(async (t) => d(await e.GET("/runs", { params: { query: {
					flow: t,
					per_page: 20
				} } })).data));
				H.value = _e(r), G.value = !1, W.value = null;
			} catch (e) {
				G.value = !0, W.value = u(e);
			} finally {
				U.value = !0;
			}
		}
		pe(B, () => void K(), { immediate: !0 });
		let q = t(async (e) => n(await e.GET("/families", { params: { query: { per_page: 100 } } }))), ye = t(c), be = t(async (e) => n(await e.GET("/channels", { params: { query: { per_page: 100 } } }))), J = w("upsert"), Y = E(null), X = de("fileInput"), Z = ue({
			format: "jsonl",
			family: "",
			category: "",
			scope: "",
			published: !1
		}), Q = E(null);
		function $(e) {
			Y.value = e.target.files?.[0] ?? null;
		}
		async function ze() {
			let e = Y.value;
			if (!S || !e) return;
			let t = new FormData();
			t.append("file", e), t.append("mode", J.value);
			let r = { run: null };
			await I.run(async () => {
				r.run = n(await S.POST("/imports", {
					body: t,
					bodySerializer: (e) => e
				})).data;
			}) && (Q.value = r.run, Y.value = null, X.value && (X.value.value = ""), await K());
		}
		async function Be() {
			if (!S) return;
			let e = { run: null };
			await L.run(async () => {
				e.run = n(await S.POST("/exports", { body: r(ve(Z)) })).data;
			}) && (Q.value = e.run, await K());
		}
		return (t, n) => {
			let r = ie, i = se, a = ae, s = oe, c = ne, u = ee, d = ce, f = te;
			return C(), v("div", Se, [y("h1", Ce, D(O(l)("showroom.transfers.title")), 1), x(f, { "screen-key": "showroom.transfers" }, {
				default: A(() => [y("div", we, [
					O(Q) ? (C(), g(r, {
						key: 0,
						color: "success",
						variant: "subtle",
						icon: "i-lucide-circle-check",
						title: O(l)("showroom.transfers.started_notice", { flow: O(Q).flow }),
						"data-testid": "showroom-transfer-started"
					}, null, 8, ["title"])) : _("", !0),
					y("div", Te, [x(s, { "data-testid": "showroom-transfer-import" }, {
						header: A(() => [y("h2", Ee, D(O(l)("showroom.transfers.import.title")), 1)]),
						default: A(() => [y("form", {
							class: "flex flex-col gap-3",
							"data-testid": "showroom-transfer-import-form",
							onSubmit: M(ze, ["prevent"])
						}, [
							x(e, { action: O(I) }, null, 8, ["action"]),
							x(i, {
								label: O(l)("showroom.transfers.import.file"),
								hint: O(l)("showroom.transfers.import.file_hint"),
								error: O(I).error("file"),
								required: ""
							}, {
								default: A(() => [y("input", {
									ref_key: "fileInput",
									ref: X,
									type: "file",
									accept: ".csv,.jsonl,text/csv",
									class: "block w-full text-sm",
									"data-testid": "showroom-transfer-import-file",
									onChange: $
								}, null, 544)]),
								_: 1
							}, 8, [
								"label",
								"hint",
								"error"
							]),
							x(i, {
								label: O(l)("showroom.transfers.import.mode"),
								error: O(I).error("mode")
							}, {
								default: A(() => [j(y("select", {
									"onUpdate:modelValue": n[0] ||= (e) => le(J) ? J.value = e : null,
									class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-transfer-import-mode"
								}, [(C(!0), v(m, null, T(O(me), (e) => (C(), v("option", {
									key: e,
									value: e
								}, D(O(l)(`showroom.transfers.import.modes.${e}`)), 9, De))), 128))], 512), [[k, O(J)]])]),
								_: 1
							}, 8, ["label", "error"]),
							y("div", null, [x(a, {
								type: "submit",
								icon: "i-lucide-upload",
								loading: O(I).busy.value,
								"data-testid": "showroom-transfer-import-submit"
							}, {
								default: A(() => [b(D(O(l)("showroom.transfers.import.submit")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32)]),
						_: 1
					}), x(s, { "data-testid": "showroom-transfer-export" }, {
						header: A(() => [y("h2", Oe, D(O(l)("showroom.transfers.export.title")), 1)]),
						default: A(() => [y("form", {
							class: "flex flex-col gap-3",
							"data-testid": "showroom-transfer-export-form",
							onSubmit: M(Be, ["prevent"])
						}, [
							x(e, { action: O(L) }, null, 8, ["action"]),
							x(i, {
								label: O(l)("showroom.transfers.export.format"),
								error: O(L).error("format")
							}, {
								default: A(() => [j(y("select", {
									"onUpdate:modelValue": n[1] ||= (e) => O(Z).format = e,
									class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-transfer-export-format"
								}, [(C(!0), v(m, null, T(O(he), (e) => (C(), v("option", {
									key: e,
									value: e
								}, D(O(l)(`showroom.transfers.export.formats.${e}`)), 9, ke))), 128))], 512), [[k, O(Z).format]])]),
								_: 1
							}, 8, ["label", "error"]),
							x(i, {
								label: O(l)("showroom.catalog.fields.family"),
								error: O(L).error("family")
							}, {
								default: A(() => [j(y("select", {
									"onUpdate:modelValue": n[2] ||= (e) => O(Z).family = e,
									class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-transfer-export-family"
								}, [y("option", Ae, D(O(l)("showroom.transfers.export.all_families")), 1), (C(!0), v(m, null, T(O(q), (e) => (C(), v("option", {
									key: e.code,
									value: e.code
								}, D(O(o)(e.labels, O(p), e.code)), 9, je))), 128))], 512), [[k, O(Z).family]])]),
								_: 1
							}, 8, ["label", "error"]),
							x(i, {
								label: O(l)("showroom.transfers.export.category_tree"),
								error: O(L).error("category")
							}, {
								default: A(() => [j(y("select", {
									"onUpdate:modelValue": n[3] ||= (e) => O(Z).category = e,
									class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-transfer-export-category"
								}, [y("option", Me, D(O(l)("showroom.transfers.export.all_categories")), 1), (C(!0), v(m, null, T(O(ye), (e) => (C(), v("option", {
									key: e.code,
									value: e.code
								}, D(O(o)(e.labels, O(p), e.code)), 9, Ne))), 128))], 512), [[k, O(Z).category]])]),
								_: 1
							}, 8, ["label", "error"]),
							x(i, {
								label: O(l)("showroom.catalog.fields.channel"),
								error: O(L).error("scope")
							}, {
								default: A(() => [j(y("select", {
									"onUpdate:modelValue": n[4] ||= (e) => O(Z).scope = e,
									class: "w-full rounded-md border border-default bg-default px-2.5 py-1.5 text-sm",
									"data-testid": "showroom-transfer-export-scope"
								}, [y("option", Pe, D(O(l)("showroom.transfers.export.no_channel")), 1), (C(!0), v(m, null, T(O(be), (e) => (C(), v("option", {
									key: e.code,
									value: e.code
								}, D(O(o)(e.labels, O(p), e.code)), 9, Fe))), 128))], 512), [[k, O(Z).scope]])]),
								_: 1
							}, 8, ["label", "error"]),
							y("label", Ie, [j(y("input", {
								"onUpdate:modelValue": n[5] ||= (e) => O(Z).published = e,
								type: "checkbox",
								"data-testid": "showroom-transfer-export-published"
							}, null, 512), [[fe, O(Z).published]]), b(" " + D(O(l)("showroom.transfers.export.published")), 1)]),
							y("div", null, [x(a, {
								type: "submit",
								icon: "i-lucide-download",
								loading: O(L).busy.value,
								"data-testid": "showroom-transfer-export-submit"
							}, {
								default: A(() => [b(D(O(l)("showroom.transfers.export.submit")), 1)]),
								_: 1
							}, 8, ["loading"])])
						], 32)]),
						_: 1
					})]),
					x(s, { "data-testid": "showroom-transfers-runs" }, {
						header: A(() => [y("h2", Le, D(O(l)("showroom.transfers.recent")), 1)]),
						default: A(() => [O(P).active ? O(G) && O(W) === 403 ? (C(), g(c, {
							key: 1,
							compact: ""
						})) : O(G) ? (C(), g(u, {
							key: 2,
							message: O(l)("showroom.transfers.runs_failed")
						}, null, 8, ["message"])) : O(U) ? O(H).length === 0 ? (C(), v("p", Re, D(O(l)("showroom.transfers.no_runs")), 1)) : (C(), g(xe, {
							key: 5,
							runs: O(H),
							"row-actions": O(V)
						}, null, 8, ["runs", "row-actions"])) : (C(), g(d, {
							key: 3,
							class: "h-24"
						})) : (C(), g(r, {
							key: 0,
							color: "neutral",
							variant: "subtle",
							icon: "i-lucide-plug",
							title: O(l)("showroom.transfers.requires_impex"),
							description: O(l)("showroom.transfers.requires_impex_hint"),
							"data-testid": "showroom-transfers-requires-impex"
						}, null, 8, ["title", "description"]))]),
						_: 1
					})
				])]),
				_: 1
			})]);
		};
	}
});
export { $ as default };
